<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderMediaSeeder extends Seeder
{
    /**
     * Sample image file names to use.
     */
    private array $sampleImages = [
        'order_image_1.jpg',
        'order_image_2.jpg',
        'order_image_3.jpg',
        'order_image_4.jpg',
        'order_image_5.jpg',
    ];

    /**
     * Run the seeder.
     */
    public function run(): void
    {
        // Create sample placeholder images first
        $this->createSampleImages();

        // Get orders without media
        $orderIds = Order::whereDoesntHave('media')->pluck('id')->toArray();

        if (empty($orderIds)) {
            echo "All orders already have media attached.\n";

            return;
        }

        $total = count($orderIds);
        $chunkSize = 500;
        $processed = 0;

        echo "Attaching media to {$total} orders...\n";

        foreach (array_chunk($orderIds, $chunkSize) as $chunk) {
            $mediaRecords = [];

            foreach ($chunk as $orderId) {
                // Attach 1-3 images per order
                $imageCount = rand(1, 3);

                for ($i = 0; $i < $imageCount; $i++) {
                    $imageName = $this->sampleImages[array_rand($this->sampleImages)];
                    $uuid = Str::uuid()->toString();

                    $mediaRecords[] = [
                        'model_type' => Order::class,
                        'model_id' => $orderId,
                        'uuid' => $uuid,
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
                        'order_column' => $i + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            DB::table('media')->insert($mediaRecords);

            $processed += count($chunk);
            echo "Progress: {$processed}/{$total} orders processed\n";
        }

        echo "Media seeding completed!\n";
    }

    /**
     * Create sample placeholder images in storage.
     */
    private function createSampleImages(): void
    {
        $storagePath = 'media/orders';

        // Ensure directory exists
        Storage::disk('public')->makeDirectory($storagePath);

        // Create simple colored placeholder images
        $colors = [
            [66, 133, 244],   // Blue
            [52, 168, 83],    // Green
            [251, 188, 5],    // Yellow
            [234, 67, 53],    // Red
            [155, 89, 182],   // Purple
        ];

        foreach ($this->sampleImages as $index => $imageName) {
            $filePath = $storagePath.'/'.$imageName;

            if (! Storage::disk('public')->exists($filePath)) {
                // Create a simple placeholder image using GD
                if (extension_loaded('gd')) {
                    $img = imagecreatetruecolor(800, 600);
                    $color = $colors[$index % count($colors)];
                    $bgColor = imagecolorallocate($img, $color[0], $color[1], $color[2]);
                    imagefill($img, 0, 0, $bgColor);

                    // Add some text
                    $textColor = imagecolorallocate($img, 255, 255, 255);
                    $text = 'Order Image '.($index + 1);
                    imagestring($img, 5, 320, 290, $text, $textColor);

                    // Save to temp file then move to storage
                    $tempPath = sys_get_temp_dir().'/'.$imageName;
                    imagejpeg($img, $tempPath, 90);
                    imagedestroy($img);

                    Storage::disk('public')->put($filePath, file_get_contents($tempPath));
                    unlink($tempPath);

                    echo "Created placeholder image: {$imageName}\n";
                } else {
                    // If GD not available, create a minimal valid JPEG
                    $minimalJpeg = $this->createMinimalJpeg();
                    Storage::disk('public')->put($filePath, $minimalJpeg);
                    echo "Created minimal placeholder: {$imageName}\n";
                }
            }
        }
    }

    /**
     * Create a minimal valid JPEG byte sequence.
     */
    private function createMinimalJpeg(): string
    {
        // Minimal 1x1 red JPEG
        return base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRof'.
            'Hh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwh'.
            'MjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAAR'.
            'CAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAA'.
            'AAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMB'.
            'AAIRAxEAPwCwAB//2Q=='
        );
    }
}
