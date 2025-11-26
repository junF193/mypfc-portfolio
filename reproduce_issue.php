<?php
$user = App\Models\User::first();
$foodLog = App\Models\FoodLog::first();

if (!$user) {
    echo "No user found.\n";
    exit;
}
if (!$foodLog) {
    echo "No food log found.\n";
    exit;
}

echo "User ID: " . $user->id . "\n";
echo "FoodLog ID: " . $foodLog->id . "\n";

// Clean up existing favorite if any
// $existing = $user->favorites()->where('source_food_log_id', $foodLog->id)->first();
// if ($existing) {
//    $existing->delete();
//    echo "Deleted existing favorite.\n";
// }

$existing = $user->favorites()->where('source_food_log_id', $foodLog->id)->first();
if ($existing) {
    echo "Found existing favorite: " . $existing->id . "\n";
    try {
        $resource = new App\Http\Resources\FavoriteResource($existing);
        $json = json_encode($resource->resolve());
        echo "Resource resolved successfully: " . $json . "\n";
    } catch (\Throwable $e) {
        echo "Resource resolution failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "Did not find existing favorite via source_food_log_id query!\n";
    // Debug: list all favorites
    foreach($user->favorites as $f) {
        echo "Fav ID: " . $f->id . ", Source: " . $f->source_food_log_id . "\n";
    }
}
