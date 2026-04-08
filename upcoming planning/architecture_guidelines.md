# Architecture Guidelines: Single Actions vs Service Classes

## The Verdict: Use "Single Action Classes"

For modern Laravel applications, **Single Action Classes** are generally preferred over massive Service Classes.

### Why? (Comparison)

| Feature | Single Action Actions | Traditional Service Classes |
| :--- | :--- | :--- |
| **Purpose** | Does **one** thing (e.g., `CreateOrder`). | Groups many things (e.g., `OrderService` does create, update, delete, notify). |
| **Maintenance** | **High**. Files are small and focused. Reading `CreateOrder.php` tells you exactly what happens. | **Low**. `OrderService.php` can grow to 2,000+ lines (the "God Class" problem). |
| **Testing** | **Easiest**. You just test `(new CreateOrder)->handle(...)`. | **Hard**. To test one method, you often have to mock dependencies needed by *other* methods in the same class. |
| **Refactoring** | Safe. Changing `CreateOrder` doesn't break `CancelOrder`. | Risky. Shared private methods in a Service class mean a change for one feature might break another. |

---

## The Recommended Pattern: "The Action"

### 1. The Structure
Create a dedicated `app/Actions` directory. Group them by domain (e.g., `app/Actions/Provider`).

### 2. The Controller (Clean)
The controller should **only** handle HTTP input and return HTTP responses. It delegates the "work" to the Action.

```php
// app/Http/Controllers/Dashboard/ProviderController.php

public function update(ProviderRequest $request, Provider $provider, UpdateProviderAction $action)
{
    // 1. The Controller validates input automatically via ProviderRequest
    
    // 2. The Controller calls the specific Action
    $action->handle($provider, $request->validated());

    // 3. The Controller returns the response
    return to_route('dashboard.providers.index')
        ->with('success', __('data updated successfully'));
}
```

### 3. The Action Class (The Logic)
This is where your business logic lives. It doesn't know about `Request` objects, making it reusable (e.g., in CLI commands or Jobs).

```php
// app/Actions/Provider/UpdateProviderAction.php

namespace App\Actions\Provider;

use App\Models\Provider;
use Illuminate\Support\Facades\DB;

class UpdateProviderAction
{
    /**
     * @param array<string, mixed> $data 
     */
    public function handle(Provider $provider, array $data): Provider
    {
        return DB::transaction(function () use ($provider, $data) {
            // Logic 1: Handle File Uploads (if passed as paths or objects)
            if (isset($data['logo'])) {
                $provider->updateLogo($data['logo']); // Helper method on model recommended
            }

            // Logic 2: Update basic attributes
            $provider->update($data);

            // Logic 3: Conceptually complex logic (e.g. syncing relations)
            if (isset($data['categories'])) {
                $this->syncCategories($provider, $data['categories']);
            }

            return $provider;
        });
    }

    protected function syncCategories(Provider $provider, array $categories): void
    {
        // ... Your complex logic here ...
    }
}
```

---

## Best Practices for Testing

This architecture makes adding tests significantly easier because you can test "Layers" separately.

### 1. Unit Testing the Action (Fast & Logic Heavy)
You don't need to fake an HTTP request or login as a user. You just pass data to the class.

```php
// tests/Unit/Actions/Provider/UpdateProviderActionTest.php

use App\Actions\Provider\UpdateProviderAction;
use App\Models\Provider;

it('updates provider name and calls sync logic', function () {
    // Arrange
    $provider = Provider::factory()->create(['name' => 'Old Name']);
    $action = app(UpdateProviderAction::class);

    // Act
    $action->handle($provider, ['name' => 'New Name']);

    // Assert
    expect($provider->fresh()->name)->toBe('New Name');
});
```

### 2. Feature Testing the Controller (HTTP & Validation)
You only check if the controller *calls* code correctly and returns the right *response*.

```php
// tests/Feature/Dashboard/ProviderControllerTest.php

use App\Models\Provider;

it('updates a provider via http request', function () {
    // Arrange
    $provider = Provider::factory()->create();
    $this->actingAsAdmin(); // Helper to login

    // Act
    $response = $this->put(route('dashboard.providers.update', $provider), [
        'name' => 'New Name',
        // ... valid data
    ]);

    // Assert: We just care about the connection/response here
    $response->assertRedirect('dashboard.providers.index');
    $response->assertSessionHas('success');
    
    // Double check DB if you want integration confidence
    expect($provider->fresh()->name)->toBe('New Name');
});
```

## Summary Recommendation
1.  **Do not** create large `ProviderService` classes.
2.  **Do** create small, specific Action classes: `CreateProvider`, `UpdateProvider`, `VerifyProvider`.
3.  **Keep Controllers Skinny**: They are just the "Receptionist" routing data.
4.  **Unit Tests** for the Actions (Logic).
5.  **Feature Tests** for the Controllers (Routes/Permissions).
