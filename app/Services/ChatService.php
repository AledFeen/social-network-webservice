<?php

namespace App\Services;

use App\Events\DeleteMessageEvent;
use App\Events\EditMessageEvent;
use App\Events\SendMessageEvent;
use App\Models\Chat;
use App\Models\dto\ChatUserDTO;
use App\Models\dto\LastMessageDTO;
use App\Models\dto\PreviewPersonalChatDTO;
use App\Models\dto\UserDTO;
use App\Models\Message;
use App\Models\MessageFile;
use App\Models\User;
use App\Models\UserChatLink;
use App\Services\Paginate\PaginatedResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChatService
{
    public function getChatId(array $request) {
        $userId = $request['user_id'];

        return Chat::whereIn('id', function ($query) {
            $query->select('chat_id')
                ->from('user_chat_links')
                ->where('user_id', Auth::id());
        })->whereIn('id', function ($query) use ($userId) {
            $query->select('chat_id')
                ->from('user_chat_links')
                ->where('user_id', $userId);
        })->first();

    }

    public function updateReadProperties(array $request): bool
    {
        $chatId = $request['chat_id'];

        $messages = Message::whereHas('link', function ($query) use ($chatId) {
            $query->where('chat_id', $chatId)
                ->where('user_id', '!=', Auth::id());
        })
            ->get();

        if(count($messages) > 0) {
            return (bool)Message::whereHas('link', function ($query) use ($chatId) {
                $query->where('chat_id', $chatId)
                    ->where('user_id', '!=', Auth::id());
            })
                ->update(['is_read' => true]);
        } else {
            return true;
        }
    }

    public function getChatUsers(array $request)
    {
        $links = UserChatLink::where('chat_id', $request['chat_id'])->pluck('user_id');
        if ($links->contains(Auth::id())) {
            $links = UserChatLink::where('chat_id', $request['chat_id'])
                ->with('user.account')
                ->get();

            return $this->getChatUsersDTOs($links);
        } else return null;
    }

    protected function getChatUsersDTOs($links)
    {
        return $links->map(function ($link) {
            return new ChatUserDTO(
                $link->id,
                new UserDTO(
                    $link->user->id,
                    $link->user->name,
                    $link->user->account->image
                )
            );
        });
    }

    public function getMessages(array $request): ?PaginatedResponse
    {
        $links = UserChatLink::where('chat_id', $request['chat_id'])->pluck('user_id');
        if ($links->contains(Auth::id())) {
            $links = UserChatLink::where('chat_id', $request['chat_id'])->pluck('id');
            $messages = Message::whereIn('link_id', $links)
                ->with('files')
                ->orderBy('created_at', 'desc')
                ->paginate(15, ['*'], 'page', $request['page_id']);

            return new PaginatedResponse(
                $messages,
                $messages->currentPage(),
                $messages->lastPage(),
                $messages->total()
            );
        } else return null;
    }

    public function getMessageForAdmin(array $request)
    {
        if(Auth::user()->role == 'admin') {
            return Message::where('id', $request['message_id'])->first();
        } else return null;
    }

    public function getPersonalChats()
    {
        $userId = Auth::id();

        $chats = Chat::whereHas('userChatLinks', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->with(['users' => function ($query) use ($userId) {
                $query->where('users.id', '!=', $userId)
                    ->with('account');
            }])
            ->with('userChatLinks')
            ->get();

        $chatsDto = $this->getPersonalChatsDTOs($chats);

        return $chatsDto->sortByDesc(fn($chat) => $chat->getLastMessage() ? Carbon::parse($chat->getLastMessage()->getCreatedAt())->timestamp : 0)->values();
    }

    protected function getPersonalChatsDTOs($chats)
    {
        return $chats->map(function ($chat) {
            if ($chat->type == 'personal') {
                return new PreviewPersonalChatDTO(
                    $chat->id,
                    $chat->type,
                    $this->getPersonalUserDto($chat),
                    $this->countNewMessages($chat),
                    $this->getLastMessageDTO($this->getLastMessage($chat))
                );
            } else return null;
        });
    }

    protected function getPersonalUserDto($chat): UserDTO
    {
        $user = $chat->users->first();
        return new UserDTO(
            $user->id,
            $user->name,
            $user->account->image
        );
    }

    protected function getLastMessageDTO($message): ?LastMessageDTO
    {
        if ($message) {

            if ($message->text != null) {
                $text = $message->text;
            } else {
                $text = $this->getLastMessageFileName($message->id);
            }

            return new LastMessageDTO(
                $message->id,
                $message->link_id,
                $message->is_read,
                $text,
                $message->created_at,
                $message->updated_at,
                new UserDTO(
                    $message->link->user->id,
                    $message->link->user->name,
                    $message->link->user->account->image
                )
            );
        } else return null;
    }

    protected function getLastMessageFileName($messageId): string
    {
        $files = MessageFile::where('message_id', $messageId)->orderBy('type')->get();
        if ($files->count() > 1) {
            $count = $files->count() - 1;
            return $files->first()->name . "|$count";
        } else {
            return $files->first()->name;
        }
    }

    protected function getLastMessage($chat)
    {
        $latestMessage = null;

        if (!$chat || !$chat->userChatLinks) {
            return 0;
        }
        foreach ($chat->userChatLinks as $link) {
            if ($latestMessage == null) {
                $latestMessage = Message::where('link_id', $link->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
            } else {
                $message = Message::where('link_id', $link->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                if ($message && $message->created_at > $latestMessage->created_at) {
                    $latestMessage = $message;
                }
            }
        }

        return Message::where('id', $latestMessage->id)
            ->with('link.user.account')
            ->first();
    }

    protected function countNewMessages($chat)
    {

        if (!$chat || !$chat->userChatLinks) {
            return 0;
        }

        $countList = [];
        foreach ($chat->userChatLinks as $link) {
            if ($link->user_id != Auth::id()) {
                $unreadCount = Message::where('link_id', $link->id)
                    ->where('is_read', false)
                    ->count();

                $countList[] = $unreadCount;
            }
        }

        $sum = 0;

        foreach ($countList as $value) {
            $sum += $value;
        }

        return $sum;
    }

    public function sendMessage(array $request): bool
    {
        if (array_key_exists('files', $request)) {
            $files = $request['files'];
        } else $files = [];

        if ($request['text'] || $files) {
            DB::beginTransaction();
            $images = [];
            $videos = [];
            $audios = [];
            $reserveFiles = [];
            try {
                $chatLink = UserChatLink::where('user_id', Auth::id())
                    ->where('chat_id', $request['chat_id'])->first();

                $createdMessage = Message::create([
                    'text' => $request['text'],
                    'link_id' => $chatLink->id
                ]);

                if ($files != null) {
                    foreach ($files as $file) {
                        $extension = $file->getClientOriginalExtension();

                        switch (true) {
                            case in_array($extension, ['jpeg', 'png', 'jpg', 'gif', 'svg']):
                                $images[] = $this->addImage($file, $createdMessage->id);
                                break;

                            case in_array($extension, ['mp4', 'mov', 'avi', 'mkv']):
                                $videos[] = $this->addVideo($file, $createdMessage->id);
                                break;

                            case in_array($extension, ['mp3', 'wav', 'ogg']):
                                $audios[] = $this->addAudio($file, $createdMessage->id);
                                break;

                            default:
                                $reserveFiles[] = $this->addDocument($file, $createdMessage->id);
                                break;
                        }
                    }
                }
                DB::commit();

                //!!!!!!!!!!!!
                $this->sendMessageBroadcast($createdMessage->id, $request['chat_id'], 'send');

                return true;
            } catch (\Throwable $e) {
                DB::rollBack();
                if ($files) {
                    $this->clearStorage($images, $videos, $audios, $reserveFiles);
                }
                report($e);
                return false;
            }
        } else return false;
    }

    public function updateTextMessage(array $request): bool
    {
        $message = Message::where('id', $request['message_id'])->first();

        $link = UserChatLink::where('id', $message->link_id)->first();

        if ($link->user_id == Auth::id()) {
            Message::where('id', $request['message_id'])->update([
                'text' => $request['text']
            ]);

            $link = UserChatLink::where('id', $message->link_id)->first();
            $this->sendMessageBroadcast($message->id, $link->chat_id, 'edit');
            return true;
        }
        return false;
    }

    public function deleteMessage(array $request): bool
    {
        $message = Message::where('id', $request['message_id'])->first();
        if($message) {
            $link = UserChatLink::where('id', $message->link_id)->first();

            if ($link->user_id == Auth::id() || Auth::user()->role == 'admin') {
                $messageFiles = MessageFile::where('message_id', $request['message_id'])->get();

                $deleted = Message::where('id', $request['message_id'])->delete();
                if ($deleted && $messageFiles) {
                    $this->deleteMessageFiles($messageFiles);
                    event(new DeleteMessageEvent($request['message_id'], $link->chat_id));
                }
                return (bool)$deleted;
            } else return false;
        } else return false;
    }

    public function createPersonalChat(array $request): bool
    {
        $firstUser = Auth::id();
        $secondUser = $request['user_id'];

        if (!$this->checkPersonalChatExist($firstUser, $secondUser)) {
            DB::beginTransaction();
            try {
                $chat = Chat::create([
                    'type' => 'personal'
                ]);

                UserChatLink::create([
                    'user_id' => $firstUser,
                    'chat_id' => $chat->id
                ]);

                UserChatLink::create([
                    'user_id' => $secondUser,
                    'chat_id' => $chat->id
                ]);

                DB::commit();
                return true;
            } catch (\Exception $e) {
                DB::rollBack();
                logger($e);
                return false;
            }
        } else {
            return false;
        }
    }

    public function deletePersonalChat(array $request): bool
    {
        $links = UserChatLink::where('chat_id', $request['chat_id'])->get();

        $isCorrectUser = false;

        foreach ($links as $link) {
            if ($link->user_id == Auth::id()) {
                $isCorrectUser = true;
            }
        }

        if ($isCorrectUser) {
            foreach ($links as $link) {
                $messages = Message::where('link_id', $link->id)->get();
                foreach ($messages as $message) {
                    $messageFiles = MessageFile::where('message_id', $message->id)->get();
                    $this->deleteMessageFiles($messageFiles);
                }
            }
            return (bool)Chat::where('id', $request['chat_id'])->delete();
        } else return false;
    }

    protected function checkPersonalChatExist(int $firstUser, int $secondUser): bool
    {
        $firstUserChats = UserChatLink::where('user_id', $firstUser)->pluck('chat_id');

        return (bool)UserChatLink::where('user_id', $secondUser)
            ->whereIn('chat_id', $firstUserChats)
            ->first();
    }

    protected function addDocument($file, int $messageId): string
    {
        $originalFileName = $file->getClientOriginalName();
        $fileName = basename(Storage::put('/private/files/messages', $file));
        MessageFile::create([
            'message_id' => $messageId,
            'type' => 'document',
            'filename' => $fileName,
            'name' => $originalFileName
        ]);
        return $fileName;
    }

    protected function addAudio($file, int $messageId): string
    {
        $originalFileName = $file->getClientOriginalName();
        $fileName = basename(Storage::put('/private/audios/messages', $file));
        MessageFile::create([
            'message_id' => $messageId,
            'type' => 'audio',
            'filename' => $fileName,
            'name' => $originalFileName
        ]);
        return $fileName;
    }

    protected function addImage($file, int $messageId): string
    {
        $originalFileName = $file->getClientOriginalName();
        $fileName = basename(Storage::put('/private/images/messages', $file));
        MessageFile::create([
            'message_id' => $messageId,
            'type' => 'photo',
            'filename' => $fileName,
            'name' => $originalFileName
        ]);
        return $fileName;
    }

    protected function addVideo($file, int $messageId): string
    {
        $originalFileName = $file->getClientOriginalName();
        $fileName = basename(Storage::put('/private/videos/messages', $file));
        MessageFile::create([
            'message_id' => $messageId,
            'type' => 'video',
            'filename' => $fileName,
            'name' => $originalFileName
        ]);
        return $fileName;
    }

    protected function deleteMessageFiles(\Illuminate\Database\Eloquent\Collection $files): void
    {
        foreach ($files as $file) {
            switch ($file->type) {
                case 'photo':
                    $this->deleteImage($file->filename);
                    break;

                case 'video':
                    $this->deleteVideo($file->filename);
                    break;

                case 'audio':
                    $this->deleteAudio($file->filename);
                    break;

                default:
                    $this->deleteFile($file->filename);
                    break;
            }
        }
    }

    protected function clearStorage(array $images, array $videos, array $audios, array $files): void
    {
        foreach ($images as $image) {
            $this->deleteImage($image);
        }

        foreach ($videos as $video) {
            $this->deleteVideo($video);
        }

        foreach ($audios as $audio) {
            $this->deleteAudio($audio);
        }

        foreach ($files as $file) {
            $this->deleteFile($file);
        }
    }

    protected function deleteImage($name): void
    {
        Storage::delete('/private/images/messages/' . $name);
    }

    protected function deleteVideo($name): void
    {
        Storage::delete('/private/videos/messages/' . $name);
    }

    protected function deleteAudio($name): void
    {
        Storage::delete('/private/audios/messages/' . $name);
    }

    protected function deleteFile($name): void
    {
        Storage::delete('/private/files/messages/' . $name);
    }

    protected function sendMessageBroadcast($messageId, $chat_id, $type): void
    {
        $message = Message::where('id', $messageId)
            ->with('files')->first();
        if($message) {
            if($type == 'send') {
                event(new SendMessageEvent($message, $chat_id));
            } else event(new EditMessageEvent($message, $chat_id));
        }
    }
}
