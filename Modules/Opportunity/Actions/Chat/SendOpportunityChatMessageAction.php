<?php

namespace Modules\Opportunity\Actions\Chat;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Support\OpportunityConversationMessenger;
use Throwable;

class SendOpportunityChatMessageAction
{
    /**
     * @param  array<int, UploadedFile>  $files
     *
     * @throws Throwable
     */
    public function handle(Conversation $conversation, Model $sender, ?string $content, array $files): ConversationMessage
    {
        return DB::transaction(function () use ($conversation, $sender, $content, $files) {
            abort_if(
                $conversation->operation_type !== Opportunity::class,
                404,
                __('opportunity.not_found'),
            );

            $messenger = new OpportunityConversationMessenger($conversation);
            $updatedConversation = $messenger->sendAs($sender, $content, $files);
            $updatedConversation->loadMissing(['lastMassage.sender', 'lastMassage.attachments']);

            return $updatedConversation->lastMassage;
        });
    }
}
