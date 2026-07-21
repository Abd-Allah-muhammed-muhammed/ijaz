<?php

namespace Database\Seeders;

use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use Modules\Marketplace\Models\Category;
use App\Models\Order;
use App\Models\Provider;
use Modules\Marketplace\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

class BulkOrdersSeeder extends Seeder
{
    private array $orderTitles = [
        'Home Cleaning Service Needed',
        'Plumbing Repair - Kitchen Sink',
        'Electrical Installation for New Office',
        'Garden Landscaping Project',
        'AC Maintenance and Repair',
        'Interior Painting - 3 Bedroom Apartment',
        'Furniture Assembly Service',
        'Moving and Relocation Assistance',
        'Deep Cleaning Before Event',
        'Carpentry Work - Custom Shelves',
        'Roof Repair and Maintenance',
        'Swimming Pool Cleaning',
        'Security System Installation',
        'Kitchen Renovation Project',
        'Bathroom Remodeling',
        'Window Installation and Repair',
        'Pest Control Service',
        'HVAC System Maintenance',
        'Flooring Installation',
        'Drywall Repair and Installation',
    ];

    private array $orderDescriptions = [
        'I need a thorough cleaning of my apartment including kitchen and bathrooms. Please bring your own supplies.',
        'The kitchen sink is leaking and needs immediate repair. The faucet also needs replacement.',
        'Setting up electrical outlets and lighting for a new office space. Approximately 50 square meters.',
        'Looking for someone to redesign my backyard garden. Need new plants, grass, and a small pathway.',
        'Annual maintenance for AC units. One of them is not cooling properly and needs inspection.',
        'Need to paint all walls and ceilings. White walls with accent colors in bedrooms.',
        'Have multiple furniture pieces that need assembly including wardrobes and dining table.',
        'Moving from a house to a new apartment. Need help with packing and transportation.',
        'Hosting an event next week and need the venue deep cleaned including windows and floors.',
        'Custom wooden shelves needed for my home office. Looking for quality craftsmanship.',
        'Roof has some leaks that need to be fixed before the rainy season.',
        'Weekly pool maintenance required. Pool size is approximately 8x4 meters.',
        'Need CCTV cameras installed around the property. About 8 cameras total.',
        'Complete kitchen renovation including cabinets, countertops, and appliances.',
        'Full bathroom remodel with new tiles, fixtures, and vanity.',
        'Replace old windows with energy-efficient double-glazed ones.',
        'Termite inspection and treatment needed for the entire property.',
        'Central AC system needs servicing and filter replacement.',
        'Install hardwood flooring in living room and bedrooms.',
        'Repair water-damaged drywall in the basement.',
    ];

    private array $offerDescriptions = [
        'I have extensive experience in this type of work. I can guarantee quality results.',
        'I am available immediately and can start as soon as you approve. Materials included.',
        'Professional service with warranty. Many similar projects completed with excellent feedback.',
        'Best price in the market with no compromise on quality. Contact me for questions.',
        'Certified professional with 10+ years of experience. Free consultation included.',
        'Quick turnaround time with attention to detail. References available upon request.',
        'Fully licensed and insured. Satisfaction guaranteed or your money back.',
        'Using only premium materials. Transparent pricing with no hidden costs.',
    ];

    private array $sampleImages = [
        'order_image_1.jpg',
        'order_image_2.jpg',
        'order_image_3.jpg',
        'order_image_4.jpg',
        'order_image_5.jpg',
    ];

    private array $messages = [
        ['sender' => 'user', 'content' => 'Hello, I saw your offer and have some questions.'],
        ['sender' => 'provider', 'content' => 'Hi! Of course, I would be happy to answer any questions.'],
        ['sender' => 'user', 'content' => 'How long will the work take approximately?'],
        ['sender' => 'provider', 'content' => 'Based on the description, I estimate 3-5 days to complete.'],
        ['sender' => 'user', 'content' => 'That sounds reasonable. Can you start this week?'],
        ['sender' => 'provider', 'content' => 'Yes, I can start on Wednesday. Would that work?'],
        ['sender' => 'user', 'content' => 'Perfect! Wednesday works great for me.'],
        ['sender' => 'provider', 'content' => 'Great! I will prepare everything and see you then.'],
        ['sender' => 'user', 'content' => 'Thank you for your professionalism.'],
        ['sender' => 'provider', 'content' => 'You are welcome! Looking forward to working with you.'],
    ];

    private array $userReviewComments = [
        'Excellent work! Very professional and completed on time.',
        'Great service, highly recommend this provider.',
        'Good job overall. Minor delays but quality work.',
        'Very satisfied with the results. Will hire again.',
        'Professional, punctual, and great attention to detail.',
        'The work exceeded my expectations. Thank you!',
        'Decent work, could improve communication.',
        'Amazing experience from start to finish.',
    ];

    private array $providerReviewComments = [
        'Great client, clear instructions and prompt payment.',
        'Pleasure to work with, very understanding.',
        'Good communication throughout the project.',
        'Excellent client, would work with again.',
        'Professional and respectful client.',
        'Clear requirements, easy to work with.',
        'Smooth project, highly recommended client.',
        'Responsive and cooperative throughout.',
    ];

    /**
     * Status distribution weights (percentage).
     */
    private array $statusWeights = [
        'new' => 8,
        'hold' => 5,
        'offer_provided' => 10,
        'payment_completed' => 12,
        'in_progress' => 20,
        'cancelled_by_provider' => 5,
        'cancelled_by_client' => 5,
        'ended_by_provider' => 15,
        'ended_by_client' => 15,
        'refunded' => 5,
    ];

    public function run(int $orderCount = 10000): void
    {
        // Get existing data
        $categoryIds = Category::pluck('id')->toArray();
        $cityIds = City::pluck('id')->toArray();
        $regionIds = Region::pluck('id')->toArray();
        $providerIds = Provider::pluck('id')->toArray();
        $userIds = User::pluck('id')->toArray();
        $skillIds = Skill::pluck('id')->toArray();

        if (empty($userIds)) {
            $this->createUsers();
            $userIds = User::pluck('id')->toArray();
        }

        $chunkSize = 500;
        $totalChunks = ceil($orderCount / $chunkSize);

        echo "Starting bulk seeding of {$orderCount} orders in {$totalChunks} chunks...\n";

        for ($chunk = 0; $chunk < $totalChunks; $chunk++) {
            $currentChunkSize = min($chunkSize, $orderCount - ($chunk * $chunkSize));

            DB::transaction(function () use ($currentChunkSize, $categoryIds, $cityIds, $regionIds, $providerIds, $userIds, $skillIds) {
                $this->seedChunk($currentChunkSize, $categoryIds, $cityIds, $regionIds, $providerIds, $userIds, $skillIds);
            });

            $completed = min(($chunk + 1) * $chunkSize, $orderCount);
            echo "Progress: {$completed}/{$orderCount} orders created\n";
        }

        echo "Seeding completed!\n";
    }

    /**
     * Get a random status based on weights.
     */
    private function getRandomStatus(): OrderStatusEnum
    {
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($this->statusWeights as $status => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return OrderStatusEnum::from($status);
            }
        }

        return OrderStatusEnum::InProgress;
    }

    /**
     * Determine if order status has an accepted offer.
     */
    private function hasAcceptedOffer(OrderStatusEnum $status): bool
    {
        return ! in_array($status, [
            OrderStatusEnum::New,
            OrderStatusEnum::Hold,
        ]);
    }

    /**
     * Determine if order status should have a conversation.
     */
    private function hasConversation(OrderStatusEnum $status): bool
    {
        return $this->hasAcceptedOffer($status);
    }

    /**
     * Determine if order is completed (for reviews).
     */
    private function isCompleted(OrderStatusEnum $status): bool
    {
        return in_array($status, [
            OrderStatusEnum::EndedByProvider,
            OrderStatusEnum::EndedByClient,
        ]);
    }

    /**
     * Get offer status based on order status.
     */
    private function getAcceptedOfferStatus(OrderStatusEnum $orderStatus): OfferStatusEnum
    {
        return match ($orderStatus) {
            OrderStatusEnum::OfferProvided => OfferStatusEnum::Accepted,
            OrderStatusEnum::CancelledByProvider,
            OrderStatusEnum::CancelledByClient => OfferStatusEnum::Cancelled,
            default => OfferStatusEnum::Paid,
        };
    }

    private function seedChunk(int $count, array $categoryIds, array $cityIds, array $regionIds, array $providerIds, array $userIds, array $skillIds = []): void
    {
        $orders = [];
        $orderUpdates = [];
        $offers = [];
        $conversations = [];
        $messages = [];
        $orderSkills = [];
        $mediaRecords = [];
        $reviews = [];

        $now = now();
        $userClass = User::class;
        $providerClass = Provider::class;
        $orderClass = Order::class;

        for ($i = 0; $i < $count; $i++) {
            $orderId = Str::uuid()->toString();
            $userId = $userIds[array_rand($userIds)];
            $categoryId = $categoryIds[array_rand($categoryIds)];
            $cityId = $cityIds[array_rand($cityIds)];
            $regionId = $regionIds[array_rand($regionIds)];

            $budgetStart = rand(500, 2000);
            $budgetEnd = $budgetStart + rand(500, 3000);
            $orderCreatedAt = $now->copy()->subDays(rand(1, 90));

            // Get random status
            $orderStatus = $this->getRandomStatus();
            $hasAccepted = $this->hasAcceptedOffer($orderStatus);
            $hasConvo = $this->hasConversation($orderStatus);
            $isComplete = $this->isCompleted($orderStatus);

            // Select random providers for offers (3-5)
            $offerCount = rand(3, 5);
            $selectedProviders = array_rand(array_flip($providerIds), min($offerCount, count($providerIds)));
            if (! is_array($selectedProviders)) {
                $selectedProviders = [$selectedProviders];
            }

            $acceptedIndex = $hasAccepted ? rand(0, count($selectedProviders) - 1) : -1;
            $acceptedOfferId = null;
            $acceptedProviderId = null;
            $acceptedPrice = null;

            foreach ($selectedProviders as $index => $providerId) {
                $offerId = Str::uuid()->toString();
                $offerPrice = rand(max(300, $budgetStart - 200), $budgetEnd + 200);
                $isAccepted = ($index === $acceptedIndex);

                if ($isAccepted) {
                    $acceptedOfferId = $offerId;
                    $acceptedProviderId = $providerId;
                    $acceptedPrice = $offerPrice;
                }

                $offerCreatedAt = $orderCreatedAt->copy()->addHours(rand(1, 72));

                // Determine offer status
                if ($isAccepted) {
                    $offerStatus = $this->getAcceptedOfferStatus($orderStatus);
                } elseif ($hasAccepted) {
                    $offerStatus = OfferStatusEnum::Rejected;
                } else {
                    $offerStatus = OfferStatusEnum::Pending;
                }

                $offers[] = [
                    'id' => $offerId,
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'provider_id' => $providerId,
                    'category_id' => $categoryId,
                    'price' => $offerPrice,
                    'description' => $this->offerDescriptions[array_rand($this->offerDescriptions)],
                    'status' => $offerStatus->value,
                    'created_at' => $offerCreatedAt,
                    'updated_at' => $offerCreatedAt,
                ];
            }

            // Calculate fees only if there's an accepted price
            $price = $acceptedPrice ?? null;
            $userFees = $price ? $price * 0.05 : 0;
            $providerFees = $price ? $price * 0.10 : 0;

            $orders[] = [
                'id' => $orderId,
                'title' => $this->orderTitles[array_rand($this->orderTitles)],
                'description' => $this->orderDescriptions[array_rand($this->orderDescriptions)],
                'user_id' => $userId,
                'provider_id' => $acceptedProviderId,
                'category_id' => $categoryId,
                'city_id' => $cityId,
                'region_id' => $regionId,
                'budget_start' => $budgetStart,
                'budget_end' => $budgetEnd,
                'price' => $price,
                'expected_time' => rand(1, 14).' days',
                'status' => $orderStatus->value,
                'accepted_offer_id' => null,
                'user_fees' => $userFees,
                'provider_fees' => $providerFees,
                'created_at' => $orderCreatedAt,
                'updated_at' => $orderCreatedAt,
            ];

            if ($hasAccepted) {
                $orderUpdates[] = [
                    'id' => $orderId,
                    'accepted_offer_id' => $acceptedOfferId,
                ];
            }

            // Attach 1-3 random skills to the order
            if (! empty($skillIds)) {
                $skillCount = rand(1, min(3, count($skillIds)));
                $selectedSkills = array_rand(array_flip($skillIds), $skillCount);
                if (! is_array($selectedSkills)) {
                    $selectedSkills = [$selectedSkills];
                }

                foreach ($selectedSkills as $skillId) {
                    $orderSkills[] = [
                        'order_id' => $orderId,
                        'skill_id' => $skillId,
                    ];
                }
            }

            // Attach 1-3 media images to the order
            $imageCount = rand(1, 3);
            for ($img = 0; $img < $imageCount; $img++) {
                $imageName = $this->sampleImages[array_rand($this->sampleImages)];
                $mediaRecords[] = [
                    'model_type' => $orderClass,
                    'model_id' => $orderId,
                    'uuid' => Str::uuid()->toString(),
                    'collection_name' => 'default',
                    'name' => pathinfo($imageName, PATHINFO_FILENAME),
                    'file_name' => $imageName,
                    'mime_type' => 'image/jpeg',
                    'disk' => 'public',
                    'conversions_disk' => 'public',
                    'size' => rand(50000, 500000),
                    'manipulations' => '[]',
                    'custom_properties' => '[]',
                    'generated_conversions' => '[]',
                    'responsive_images' => '[]',
                    'order_column' => $img + 1,
                    'created_at' => $orderCreatedAt,
                    'updated_at' => $orderCreatedAt,
                ];
            }

            // Create conversation only if order has accepted offer
            if ($hasConvo && $acceptedProviderId) {
                $conversationId = Str::uuid()->toString();
                $conversationCreatedAt = $orderCreatedAt->copy()->addHours(rand(1, 24));

                // More messages for completed orders
                $messageCount = $isComplete ? rand(8, 15) : rand(4, 10);
                $messageTime = $conversationCreatedAt->copy();
                $lastMessageId = null;
                $lastMessageAt = null;

                for ($m = 0; $m < $messageCount; $m++) {
                    $messageId = Str::uuid()->toString();
                    $messageData = $this->messages[$m % count($this->messages)];
                    $messageTime = $messageTime->copy()->addHours(rand(1, 12));

                    $isUserSender = $messageData['sender'] === 'user';
                    $isRead = $isComplete ? true : (rand(0, 100) < 70);

                    $messages[] = [
                        'id' => $messageId,
                        'conversation_id' => $conversationId,
                        'sender_type' => $isUserSender ? $userClass : $providerClass,
                        'sender_id' => $isUserSender ? $userId : $acceptedProviderId,
                        'receiver_type' => $isUserSender ? $providerClass : $userClass,
                        'receiver_id' => $isUserSender ? $acceptedProviderId : $userId,
                        'content' => $messageData['content'],
                        'read_at' => $isRead ? $messageTime->copy()->addMinutes(rand(5, 120)) : null,
                        'read_by_type' => $isRead ? ($isUserSender ? $providerClass : $userClass) : null,
                        'read_by_id' => $isRead ? ($isUserSender ? $acceptedProviderId : $userId) : null,
                        'has_attachments' => false,
                        'deleted_at' => null,
                        'created_at' => $messageTime,
                        'updated_at' => $messageTime,
                    ];

                    $lastMessageId = $messageId;
                    $lastMessageAt = $messageTime;
                }

                $conversations[] = [
                    'id' => $conversationId,
                    'user1_type' => $userClass,
                    'user1_id' => $userId,
                    'user2_type' => $providerClass,
                    'user2_id' => $acceptedProviderId,
                    'operation_type' => $orderClass,
                    'operation_id' => $orderId,
                    'last_message_id' => null,
                    'last_message_at' => $lastMessageAt,
                    'created_at' => $conversationCreatedAt,
                    'updated_at' => $lastMessageAt,
                ];

                // Add reviews for completed orders
                if ($isComplete && $acceptedProviderId) {
                    $reviewCreatedAt = $lastMessageAt ? $lastMessageAt->copy()->addHours(rand(1, 48)) : $orderCreatedAt->copy()->addDays(rand(5, 10));

                    // User reviews Provider
                    $reviews[] = [
                        'reviewer_type' => $userClass,
                        'reviewer_id' => $userId,
                        'reviewee_type' => $providerClass,
                        'reviewee_id' => $acceptedProviderId,
                        'operation_type' => $orderClass,
                        'operation_id' => $orderId,
                        'rating' => rand(3, 5),
                        'comment' => $this->userReviewComments[array_rand($this->userReviewComments)],
                        'created_at' => $reviewCreatedAt,
                        'updated_at' => $reviewCreatedAt,
                    ];

                    // Provider reviews User
                    $reviews[] = [
                        'reviewer_type' => $providerClass,
                        'reviewer_id' => $acceptedProviderId,
                        'reviewee_type' => $userClass,
                        'reviewee_id' => $userId,
                        'operation_type' => $orderClass,
                        'operation_id' => $orderId,
                        'rating' => rand(3, 5),
                        'comment' => $this->providerReviewComments[array_rand($this->providerReviewComments)],
                        'created_at' => $reviewCreatedAt->copy()->addHours(rand(1, 24)),
                        'updated_at' => $reviewCreatedAt->copy()->addHours(rand(1, 24)),
                    ];
                }
            }
        }

        // Bulk insert in correct order
        DB::table('orders')->insert($orders);
        DB::table('order_offers')->insert($offers);

        foreach ($orderUpdates as $update) {
            DB::table('orders')
                ->where('id', $update['id'])
                ->update(['accepted_offer_id' => $update['accepted_offer_id']]);
        }

        if (! empty($conversations)) {
            DB::table('conversations')->insert($conversations);
        }

        foreach (array_chunk($messages, 1000) as $messageChunk) {
            DB::table('conversation_messages')->insert($messageChunk);
        }

        if (! empty($orderSkills)) {
            foreach (array_chunk($orderSkills, 1000) as $skillChunk) {
                DB::table('order_skill')->insert($skillChunk);
            }
        }

        if (! empty($mediaRecords)) {
            foreach (array_chunk($mediaRecords, 1000) as $mediaChunk) {
                DB::table('media')->insert($mediaChunk);
            }
        }

        if (! empty($reviews)) {
            DB::table('reviews')->insert($reviews);
        }
    }

    private function createUsers(): void
    {
        $testUsers = [
            ['f_name' => 'Ahmed', 'l_name' => 'Al-Rashid', 'email' => 'ahmed.rashid@test.com', 'phone' => '966501234567'],
            ['f_name' => 'Sara', 'l_name' => 'Mohammed', 'email' => 'sara.mohammed@test.com', 'phone' => '966502345678'],
            ['f_name' => 'Omar', 'l_name' => 'Hassan', 'email' => 'omar.hassan@test.com', 'phone' => '966503456789'],
            ['f_name' => 'Fatima', 'l_name' => 'Ali', 'email' => 'fatima.ali@test.com', 'phone' => '966504567890'],
            ['f_name' => 'Khalid', 'l_name' => 'Ibrahim', 'email' => 'khalid.ibrahim@test.com', 'phone' => '966505678901'],
            ['f_name' => 'Layla', 'l_name' => 'Ahmad', 'email' => 'layla.ahmad@test.com', 'phone' => '966506789012'],
            ['f_name' => 'Mohammed', 'l_name' => 'Saleh', 'email' => 'mohammed.saleh@test.com', 'phone' => '966507890123'],
            ['f_name' => 'Nora', 'l_name' => 'Abdullah', 'email' => 'nora.abdullah@test.com', 'phone' => '966508901234'],
            ['f_name' => 'Yusuf', 'l_name' => 'Mahmoud', 'email' => 'yusuf.mahmoud@test.com', 'phone' => '966509012345'],
            ['f_name' => 'Aisha', 'l_name' => 'Hassan', 'email' => 'aisha.hassan@test.com', 'phone' => '966500123456'],
        ];

        $now = now();
        $users = [];

        foreach ($testUsers as $userData) {
            $users[] = array_merge($userData, [
                'password' => bcrypt('password'),
                'language' => 'ar',
                'latitude' => rand(2400, 2600) / 100,
                'longitude' => rand(4600, 4800) / 100,
                'status' => 'active',
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('users')->insertOrIgnore($users);
    }
}
