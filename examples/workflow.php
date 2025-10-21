<?php
require __DIR__.'/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\User;
use tommyknocker\chain\tests\fixtures\Profile;

/**
 * Real-world example: User registration with profile creation
 * 
 * Workflow:
 * 1. Create new user
 * 2. Validate email
 * 3. Set initial parameters based on age
 * 4. Switch to profile creation (context switching)
 * 5. Configure profile with conditional logic
 * 6. Apply business rules
 * 7. Return final result
 */

// ==================== Example utilities ====================

class ExampleLogger
{
    public static function info(string $message): void
    {
        echo "[INFO] " . date('H:i:s') . " - $message\n";
    }

    public static function success(string $message): void
    {
        echo "\033[32m[SUCCESS]\033[0m " . date('H:i:s') . " - $message\n";
    }
}

// Email validator
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ==================== MAIN EXAMPLE ====================

echo "\n🚀 Complex workflow example: User → Profile\n";
echo str_repeat("=", 60) . "\n\n";

// Example 1: Adult user with premium subscription
echo "📝 Scenario 1: Adult user (Premium)\n";
echo str_repeat("-", 60) . "\n";

$profileData = Chain::of(User::class, 'Alice Smith', 28)
    // Step 1: Set and validate email
    ->tap(fn($u) => ExampleLogger::info("Registering user: {$u->getName()}"))
    ->setEmail('alice.smith@example.com')
    ->tap(function($user) {
        if (!isValidEmail($user->getEmail())) {
            throw new InvalidArgumentException("Invalid email: {$user->getEmail()}");
        }
        ExampleLogger::info("Email is valid: {$user->getEmail()}");
    })
    
    // Step 2: Verify user
    ->verify()
    ->tap(fn($u) => ExampleLogger::success("User verified"))
    
    // Step 3: Conditional logic for adult users
    ->when(
        fn($user) => $user->isAdult(),
        function($chain) {
            ExampleLogger::info("User is adult - unlocking features");
            $chain->addRole('adult')
                  ->upgradeToPremium()
                  ->tap(fn() => ExampleLogger::success("Premium status activated"));
        }
    )
    
    // Step 4: Check for additional roles
    ->unless(
        fn($user) => $user->getAge() < 21,
        fn($chain) => $chain->addRole('full-access')
    )
    
    // Step 5: Switch to profile creation (map)
    ->tap(fn() => ExampleLogger::info("Switching to profile creation..."))
    ->map(fn($user) => new Profile($user))
    
    // Step 6: Configure profile
    ->setBio("Senior Developer | Coffee enthusiast ☕ | Love coding in PHP")
    ->setAvatar('https://cdn.example.com/avatars/alice-smith.jpg')
    ->tap(fn($p) => ExampleLogger::info("Profile completed: {$p->getCompleteness()}%"))
    
    // Step 7: Conditional premium features
    ->when(
        fn($profile) => $profile->getUser()->isPremium(),
        function($chain) {
            ExampleLogger::info("Activating premium profile features");
            $chain->enablePremiumFeatures()
                  ->addNotification('Welcome to Premium! 🎉')
                  ->addNotification('You have a free month');
        },
        fn($chain) => $chain->addNotification('Try Premium for 14 days!')
    )
    
    // Step 8: Final processing pipeline
    ->pipe(
        // Add welcome notification
        fn($profile) => $profile->addNotification("Welcome, {$profile->getUser()->getName()}!"),
        // Check profile completeness
        function($profile) {
            if ($profile->getCompleteness() >= 80) {
                ExampleLogger::success("Profile {$profile->getCompleteness()}% complete");
                $profile->addNotification('🏆 Your profile is almost perfect!');
            }
            return $profile;
        },
        // Convert to array for API response
        fn($profile) => $profile->toArray()
    )
    ->get();

print_r($profileData);

echo "\n" . str_repeat("=", 60) . "\n\n";

// Example 2: Minor user
echo "📝 Scenario 2: Minor user\n";
echo str_repeat("-", 60) . "\n";

$teenProfile = Chain::of(User::class, 'Bob Johnson', 16)
    ->tap(fn($u) => ExampleLogger::info("Registering user: {$u->getName()}, age: {$u->getAge()}"))
    ->setEmail('bob.johnson@school.edu')
    ->verify()
    
    // Minors do NOT get premium
    ->when(
        fn($user) => $user->isAdult(),
        fn($chain) => $chain->upgradeToPremium()->addRole('adult'),
        function($chain) {
            ExampleLogger::info("User is minor - limited access");
            $chain->addRole('teen');
        }
    )
    
    // Switch to profile
    ->map(fn($user) => new Profile($user))
    ->setBio("Student | Learning to code 🎓")
    ->setPreference('parental_control', true)
    ->setPreference('theme', 'light')
    
    // Special notifications for minors
    ->unless(
        fn($profile) => $profile->getUser()->isPremium(),
        fn($chain) => $chain
            ->addNotification('Hello! We\'re glad to see you 👋')
            ->addNotification('Premium will be available after 18')
    )
    
    ->pipe(
        fn($profile) => $profile->toArray()
    )
    ->get();

print_r($teenProfile);

echo "\n" . str_repeat("=", 60) . "\n\n";

// Example 3: Branching with clone()
echo "📝 Scenario 3: Branching - different processing paths\n";
echo str_repeat("-", 60) . "\n";

$baseUser = Chain::of(User::class, 'Charlie Davis', 25)
    ->setEmail('charlie@example.com')
    ->verify();

// Path A: Registration with premium
$premiumPath = $baseUser->clone()
    ->upgradeToPremium()
    ->addRole('premium')
    ->map(fn($user) => new Profile($user))
    ->enablePremiumFeatures()
    ->setBio('Premium user with full features')
    ->pipe(fn($p) => [
        'plan' => 'premium',
        'user' => $p->getUser()->getName(),
        'features' => $p->getPreferences()
    ])
    ->get();

// Path B: Free registration
$freePath = $baseUser->clone()
    ->addRole('free')
    ->map(fn($user) => new Profile($user))
    ->setBio('Free user exploring the platform')
    ->pipe(fn($p) => [
        'plan' => 'free',
        'user' => $p->getUser()->getName(),
        'features' => $p->getPreferences()
    ])
    ->get();

// Original user unchanged
$original = $baseUser->get();

echo "Premium path:\n";
print_r($premiumPath);

echo "\nFree path:\n";
print_r($freePath);

echo "\nOriginal user (unchanged):\n";
echo "Name: {$original->getName()}, Premium: " . ($original->isPremium() ? 'Yes' : 'No') . "\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ All scenarios completed successfully!\n\n";

/**
 * LIBRARY FEATURES DEMONSTRATED:
 * 
 * ✅ Chain::of(Class::class, ...$args) - instantiate via constructor
 * ✅ ->tap() - side effects (logging, validation)
 * ✅ ->map() - context switching from User to Profile
 * ✅ ->when() - conditional logic
 * ✅ ->unless() - inverse conditional logic
 * ✅ ->pipe() - functional pipeline
 * ✅ ->clone() - branching for different scenarios
 * ✅ ->get() - retrieve final result
 * ✅ Automatic context switching during fluent chaining
 * ✅ Combining all methods in real-world workflow
 */

