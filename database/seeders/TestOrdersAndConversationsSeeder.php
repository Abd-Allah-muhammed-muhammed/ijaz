<?php

namespace Database\Seeders;

use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Models\Category;
use App\Models\City;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Order;
use App\Models\OrderOffer;
use App\Models\Provider;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestOrdersAndConversationsSeeder extends Seeder
{
    /**
     * Sample conversation messages for realistic test data.
     *
     * @var array<int, array{sender: string, content: string}>
     */
    private array $sampleConversations = [
        [
            ['sender' => 'user', 'content' => 'Hello, I saw your offer and I have some questions about the service.'],
            ['sender' => 'provider', 'content' => 'Hi! Of course, I would be happy to answer any questions. What would you like to know?'],
            ['sender' => 'user', 'content' => 'How long will the work take approximately?'],
            ['sender' => 'provider', 'content' => 'Based on the description, I estimate it will take about 3-5 days to complete everything properly.'],
            ['sender' => 'user', 'content' => 'That sounds reasonable. Can you start this week?'],
            ['sender' => 'provider', 'content' => 'Yes, I can start on Wednesday. Would that work for you?'],
            ['sender' => 'user', 'content' => 'Perfect! Wednesday works great for me.'],
        ],
        [
            ['sender' => 'user', 'content' => 'Hi, I need more details about your pricing.'],
            ['sender' => 'provider', 'content' => 'Hello! The price I quoted includes all materials and labor.'],
            ['sender' => 'user', 'content' => 'Are there any additional costs I should be aware of?'],
            ['sender' => 'provider', 'content' => 'No hidden costs. If anything extra is needed, I will discuss it with you first.'],
            ['sender' => 'user', 'content' => 'Great, that gives me confidence. Let us proceed.'],
            ['sender' => 'provider', 'content' => 'Excellent! I will prepare everything and contact you to schedule.'],
        ],
        [
            ['sender' => 'provider', 'content' => 'Thank you for accepting my offer! When would be a good time to discuss the project details?'],
            ['sender' => 'user', 'content' => 'Thanks for reaching out. I am available tomorrow afternoon.'],
            ['sender' => 'provider', 'content' => 'Tomorrow at 2 PM works for me. Should I call you or would you prefer to meet?'],
            ['sender' => 'user', 'content' => 'A call would be fine. Here is my number in case you need it.'],
            ['sender' => 'provider', 'content' => 'Perfect, I have noted it down. Talk to you tomorrow!'],
            ['sender' => 'user', 'content' => 'Looking forward to it. Thank you!'],
        ],
        [
            ['sender' => 'user', 'content' => 'I wanted to confirm some details before we start.'],
            ['sender' => 'provider', 'content' => 'Sure, what would you like to confirm?'],
            ['sender' => 'user', 'content' => 'Will you bring all the necessary equipment?'],
            ['sender' => 'provider', 'content' => 'Yes, I will bring everything needed. You do not need to prepare anything.'],
            ['sender' => 'user', 'content' => 'What about parking? Is that an issue?'],
            ['sender' => 'provider', 'content' => 'If you could save me a spot near the entrance, that would help with unloading.'],
            ['sender' => 'user', 'content' => 'No problem, I will make sure there is space for you.'],
            ['sender' => 'provider', 'content' => 'Thank you! See you on the scheduled day.'],
        ],
        [
            ['sender' => 'provider', 'content' => 'Hi! I noticed your order and wanted to introduce myself. I have 5 years of experience in this field.'],
            ['sender' => 'user', 'content' => 'That is impressive! Can you share some examples of your previous work?'],
            ['sender' => 'provider', 'content' => 'Absolutely! I can send you photos of recent projects I have completed.'],
            ['sender' => 'user', 'content' => 'That would be helpful. Please send them when you can.'],
            ['sender' => 'provider', 'content' => 'I will send them tonight. Also, I offer a satisfaction guarantee on all my work.'],
            ['sender' => 'user', 'content' => 'That sounds reassuring. I appreciate your professionalism.'],
        ],
    ];

    /**
     * Sample order titles for variety.
     *
     * @var array<int, string>
     */
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
    ];

    /**
     * Sample order descriptions.
     *
     * @var array<int, string>
     */
    private array $orderDescriptions = [
        'I need a thorough cleaning of my 2-bedroom apartment including kitchen and bathrooms. Please bring your own supplies.',
        'The kitchen sink is leaking and needs immediate repair. The faucet also needs to be replaced with a new one.',
        'Setting up electrical outlets and lighting for a new office space. Approximately 50 square meters.',
        'Looking for someone to redesign my backyard garden. Need new plants, grass, and a small pathway.',
        'Annual maintenance for 3 AC units. One of them is not cooling properly and needs inspection.',
        'Need to paint all walls and ceilings in a 3-bedroom apartment. White walls with accent colors in bedrooms.',
        'Have multiple IKEA furniture pieces that need assembly including 2 wardrobes and a dining table.',
        'Moving from a 3-bedroom house to a new apartment. Need help with packing and transportation.',
        'Hosting an event next week and need the venue deep cleaned including windows and floors.',
        'Custom wooden shelves needed for my home office. Looking for quality craftsmanship.',
    ];

    public function run(): void
    {
        // Get existing data
        $categoryIds = Category::pluck('id')->toArray();
        $cityIds = City::pluck('id')->toArray();
        $regionIds = Region::pluck('id')->toArray();
        $providerIds = Provider::pluck('id')->toArray();

        if (empty($categoryIds) || empty($cityIds) || empty($regionIds) || empty($providerIds)) {
            throw new \Exception('Missing required data. Please ensure categories, cities, regions, and providers exist.');
        }

        // Create additional test users
        $users = $this->createUsers();

        // Create orders with offers, accepted offers, and conversations
        $orderCount = 10;

        for ($i = 0; $i < $orderCount; $i++) {
            $user = $users[array_rand($users)];
            $categoryId = $categoryIds[array_rand($categoryIds)];
            $cityId = $cityIds[array_rand($cityIds)];
            $regionId = $regionIds[array_rand($regionIds)];

            // Create order
            $budgetStart = fake()->numberBetween(500, 2000);
            $budgetEnd = $budgetStart + fake()->numberBetween(500, 3000);
            $price = fake()->numberBetween($budgetStart, $budgetEnd);

            // Generate order creation date (between 30 and 10 days ago)
            $orderCreatedAt = now()->subDays(fake()->numberBetween(10, 30));

            $order = Order::create([
                'id' => Str::uuid()->toString(),
                'title' => $this->orderTitles[$i % count($this->orderTitles)],
                'description' => $this->orderDescriptions[$i % count($this->orderDescriptions)],
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'city_id' => $cityId,
                'region_id' => $regionId,
                'budget_start' => $budgetStart,
                'budget_end' => $budgetEnd,
                'expected_time' => fake()->numberBetween(1, 14).' days',
                'status' => OrderStatusEnum::InProgress,
                'price' => $price,
                'user_fees' => $price * 0.05,
                'provider_fees' => $price * 0.10,
                'created_at' => $orderCreatedAt,
            ]);

            // Create multiple offers (3-5 per order)
            $offerCount = fake()->numberBetween(3, 5);
            $shuffledProviders = collect($providerIds)->shuffle()->take($offerCount)->toArray();
            $offers = [];

            foreach ($shuffledProviders as $index => $providerId) {
                $offerPrice = fake()->numberBetween($budgetStart - 200, $budgetEnd + 200);
                // Offer created 1-5 days after order
                $offerCreatedAt = (clone $orderCreatedAt)->addDays(fake()->numberBetween(1, 5));

                $offer = OrderOffer::create([
                    'id' => Str::uuid()->toString(),
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'provider_id' => $providerId,
                    'category_id' => $categoryId,
                    'price' => $offerPrice,
                    'description' => $this->generateOfferDescription(),
                    'status' => OfferStatusEnum::Pending,
                    'created_at' => $offerCreatedAt,
                ]);

                $offers[] = $offer;
            }

            // Accept one random offer
            $acceptedOffer = $offers[array_rand($offers)];
            $acceptedOffer->update(['status' => OfferStatusEnum::Accepted]);

            // Update order with accepted offer and provider
            $order->update([
                'accepted_offer_id' => $acceptedOffer->id,
                'provider_id' => $acceptedOffer->provider_id,
                'price' => $acceptedOffer->price,
            ]);

            // Reject other offers
            foreach ($offers as $offer) {
                if ($offer->id !== $acceptedOffer->id) {
                    $offer->update(['status' => OfferStatusEnum::Rejected]);
                }
            }

            // Create conversation for this order
            $provider = Provider::find($acceptedOffer->provider_id);
            $conversation = $this->createConversation($order, $user, $provider);

            // Create messages
            $this->createMessages($conversation, $user, $provider);

        }
    }

    /**
     * Create test users.
     *
     * @return array<int, User>
     */
    private function createUsers(): array
    {
        $testUsers = [
            ['f_name' => 'Ahmed', 'l_name' => 'Al-Rashid', 'email' => 'ahmed.rashid@test.com', 'phone' => '966501234567'],
            ['f_name' => 'Sara', 'l_name' => 'Mohammed', 'email' => 'sara.mohammed@test.com', 'phone' => '966502345678'],
            ['f_name' => 'Omar', 'l_name' => 'Hassan', 'email' => 'omar.hassan@test.com', 'phone' => '966503456789'],
            ['f_name' => 'Fatima', 'l_name' => 'Ali', 'email' => 'fatima.ali@test.com', 'phone' => '966504567890'],
            ['f_name' => 'Khalid', 'l_name' => 'Ibrahim', 'email' => 'khalid.ibrahim@test.com', 'phone' => '966505678901'],
        ];

        $users = [];

        foreach ($testUsers as $userData) {
            $users[] = User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'password' => bcrypt('password'),
                    'language' => 'ar',
                    'latitude' => fake()->latitude(24, 26),
                    'longitude' => fake()->longitude(46, 48),
                    'status' => 'active',
                    'email_verified_at' => now(),
                ])
            );
        }

        return $users;
    }

    /**
     * Generate offer description.
     */
    private function generateOfferDescription(): string
    {
        $descriptions = [
            'I have extensive experience in this type of work. I can guarantee quality results and timely completion.',
            'I am available immediately and can start as soon as you approve. All materials are included in my price.',
            'Professional service with warranty. I have completed many similar projects with excellent feedback.',
            'Best price in the market with no compromise on quality. Contact me for any questions.',
            'Certified professional with 10+ years of experience. Free consultation included.',
            'Quick turnaround time with attention to detail. Previous clients are happy to provide references.',
        ];

        return $descriptions[array_rand($descriptions)];
    }

    /**
     * Create conversation for order.
     */
    private function createConversation(Order $order, User $user, Provider $provider): Conversation
    {
        return Conversation::create([
            'id' => Str::uuid()->toString(),
            'user1_type' => User::class,
            'user1_id' => $user->id,
            'user2_type' => Provider::class,
            'user2_id' => $provider->id,
            'operation_type' => Order::class,
            'operation_id' => $order->id,
            'created_at' => $order->created_at,
        ]);
    }

    /**
     * Create messages for conversation.
     */
    private function createMessages(Conversation $conversation, User $user, Provider $provider): void
    {
        $conversationTemplate = $this->sampleConversations[array_rand($this->sampleConversations)];
        $messageTime = \Carbon\Carbon::parse($conversation->created_at);
        $lastMessage = null;

        foreach ($conversationTemplate as $messageData) {
            // Add random time gap between messages (1 hour to 1 day)
            $messageTime = $messageTime->copy()->addHours(fake()->numberBetween(1, 24));

            if ($messageData['sender'] === 'user') {
                $sender = $user;
                $senderType = User::class;
                $receiver = $provider;
                $receiverType = Provider::class;
            } else {
                $sender = $provider;
                $senderType = Provider::class;
                $receiver = $user;
                $receiverType = User::class;
            }

            // Randomly mark some messages as read
            $isRead = fake()->boolean(70);

            $message = ConversationMessage::create([
                'id' => Str::uuid()->toString(),
                'conversation_id' => $conversation->id,
                'sender_type' => $senderType,
                'sender_id' => $sender->id,
                'receiver_type' => $receiverType,
                'receiver_id' => $receiver->id,
                'content' => $messageData['content'],
                'read_at' => $isRead ? $messageTime->copy()->addMinutes(fake()->numberBetween(5, 60)) : null,
                'read_by_type' => $isRead ? $receiverType : null,
                'read_by_id' => $isRead ? $receiver->id : null,
                'has_attachments' => false,
                'created_at' => $messageTime,
                'updated_at' => $messageTime,
            ]);

            $lastMessage = $message;
        }

        // Update conversation with last message info
        if ($lastMessage) {
            $conversation->update([
                'last_message_id' => $lastMessage->id,
                'last_message_at' => $lastMessage->created_at,
            ]);
        }
    }
}
