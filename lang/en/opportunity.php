<?php

return [
    'status' => [
        'new' => 'New',
        'offer_accepted' => 'Offer Accepted',
        'in_progress' => 'In Progress',
        'ended' => 'Ended',
        'cancelled' => 'Cancelled',
    ],
    'offer_status' => [
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],

    'created_successfully' => 'Opportunity created successfully',
    'updated_successfully' => 'Opportunity updated successfully',
    'deleted_successfully' => 'Opportunity deleted successfully',
    'offer_submitted_successfully' => 'Offer submitted successfully',
    'offer_accepted_successfully' => 'Offer accepted successfully',
    'offer_rejected_successfully' => 'Offer rejected successfully',
    'comment_added_successfully' => 'Comment added successfully',
    'comment_deleted_successfully' => 'Comment deleted successfully',
    'media_deleted_successfully' => 'Media deleted successfully',

    'not_found' => 'Opportunity not found',
    'unauthorized' => 'You are not authorized to perform this action',
    'cannot_delete_non_new' => 'You can only delete opportunities with status New',
    'cannot_delete_media_non_new' => 'You can only delete media when opportunity status is New',
    'cannot_submit_offer_non_new' => 'Offers can only be submitted on open opportunities',
    'cannot_accept_offer' => 'You cannot accept an offer for this opportunity',
    'cannot_reject_offer' => 'You cannot reject this offer',
    'offer_not_belong_to_opportunity' => 'This offer does not belong to the specified opportunity',
];
