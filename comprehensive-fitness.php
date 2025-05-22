<?php
/*
Plugin Name: Comprehensive Fitness Planner
Description: Calculates nutritional needs and provides diet/workout plans
Version: 1.0
Author: Your Name
*/

// Enqueue styles
function cfp_enqueue_styles() {
    wp_enqueue_style('cfp-styles', plugins_url('cfp-styles.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'cfp_enqueue_styles');

// Main form shortcode
function cfp_main_form() {
    ob_start(); ?>
    
    <div class="cfp-form-container">
        <form method="post" class="cfp-form">
            <h2>Personal Fitness Planner</h2>
            
            <div class="cfp-form-group">
                <label>Weight (kg)</label>
                <input type="number" name="weight" step="0.1" required>
            </div>

            <div class="cfp-form-group">
                <label>Height (cm)</label>
                <input type="number" name="height" required>
            </div>

            <div class="cfp-form-group">
                <label>Age</label>
                <input type="number" name="age" required>
            </div>

            <div class="cfp-form-group">
                <label>Gender</label>
                <select name="gender" required>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>

            <div class="cfp-form-group">
                <label>Activity Level</label>
                <select name="activity" required>
                    <option value="1.2">Sedentary</option>
                    <option value="1.375">Light Exercise</option>
                    <option value="1.55">Moderate Exercise</option>
                    <option value="1.725">Active</option>
                    <option value="1.9">Very Active</option>
                </select>
            </div>

            <div class="cfp-form-group">
                <label>Goal</label>
                <select name="goal" required>
                    <option value="loss">Weight Loss</option>
                    <option value="maintain">Maintain Weight</option>
                    <option value="gain">Weight Gain</option>
                </select>
            </div>

            <div class="cfp-form-group">
                <label>Supplements</label>
                <div class="cfp-checkbox-group">
                    <label><input type="checkbox" name="supplements[]" value="whey"> Whey Protein</label>
                    <label><input type="checkbox" name="supplements[]" value="creatine"> Creatine</label>
                    <label><input type="checkbox" name="supplements[]" value="bcaa"> BCAA</label>
                </div>
            </div>

            <button type="submit" name="cfp_submit">Generate Plan</button>
        </form>
    </div>

    <?php
    if(isset($_POST['cfp_submit'])) {
        cfp_generate_plan(
            floatval($_POST['weight']),
            intval($_POST['height']),
            intval($_POST['age']),
            sanitize_text_field($_POST['gender']),
            floatval($_POST['activity']),
            sanitize_text_field($_POST['goal']),
            isset($_POST['supplements']) ? $_POST['supplements'] : []
        );
    }
    
    return ob_get_clean();
}
add_shortcode('fitness_planner', 'cfp_main_form');

function cfp_generate_plan($weight, $height, $age, $gender, $activity, $goal, $supplements) {
    // Calculate BMR
    if($gender == 'male') {
        $bmr = 88.362 + (13.397 * $weight) + (4.799 * $height) - (5.677 * $age);
    } else {
        $bmr = 447.593 + (9.247 * $weight) + (3.098 * $height) - (4.330 * $age);
    }
    
    // Calculate TDEE
    $tdee = $bmr * $activity;
    
    // Adjust for goal
    switch($goal) {
        case 'loss': $calories = $tdee - 500; break;
        case 'gain': $calories = $tdee + 500; break;
        default: $calories = $tdee;
    }
    
    // Calculate protein needs
    $protein = ($goal == 'gain') ? $weight * 2.2 : $weight * 1.8;
    
    // Process supplements
    $supplement_info = [
        'whey' => ['cal' => 120, 'protein' => 25],
        'creatine' => ['cal' => 5, 'protein' => 0],
        'bcaa' => ['cal' => 10, 'protein' => 0]
    ];
    
    $supp_calories = 0;
    $supp_protein = 0;
    foreach($supplements as $supp) {
        $supp_calories += $supplement_info[$supp]['cal'];
        $supp_protein += $supplement_info[$supp]['protein'];
    }
    
    // Get meal plan
    $meals = cfp_get_meal_plan($goal);
    
    // Get workout plan
    $workout = cfp_get_workout_plan($goal);
    
    // Display results
    echo '<div class="cfp-results">';
    
    // Nutritional Summary
    echo '<div class="cfp-summary">';
    echo '<h3>Your Daily Targets</h3>';
    echo '<p>Calories: '.round($calories).' kcal</p>';
    echo '<p>Protein: '.round($protein).'g'.($supp_protein > 0 ? ' + '.$supp_protein.'g from supplements' : '').'</p>';
    echo '</div>';
    
    // Meal Plan
    echo '<div class="cfp-meal-plan">';
    echo '<h3>Daily Meal Plan</h3>';
    foreach($meals as $time => $meal_group) {
        echo '<div class="cfp-meal-time-group">';
        echo '<h4>'.$time.'</h4>';
        foreach($meal_group as $meal) {
            echo '<div class="cfp-meal-card">';
            echo '<p>'.$meal['name'].'</p>';
            echo '<div class="cfp-macros">';
            echo '<span>'.$meal['cal'].' kcal</span>';
            echo '<span>'.$meal['protein'].'g protein</span>';
            echo '</div></div>';
        }
        echo '</div>';
    }
    echo '</div>';
    
    
    // Workout Plan
    echo '<div class="cfp-workout-plan">';
    echo '<h3>Home Workout Routine</h3>';
    echo '<ul>';
    foreach($workout as $exercise) {
        echo '<li>'.$exercise.'</li>';
    }
    echo '</ul></div>';
    
    echo '</div>';
}

function cfp_get_meal_plan($goal) {
    $plans = [
        'loss' => [
            'Breakfast' => [
                ['name' => 'Egg white veggie omelette', 'cal' => 280, 'protein' => 30],
                ['name' => 'Greek yogurt with chia seeds', 'cal' => 250, 'protein' => 25],
                ['name' => 'Smoked salmon avocado toast', 'cal' => 320, 'protein' => 28],
                ['name' => 'Protein spinach smoothie', 'cal' => 240, 'protein' => 32],
                ['name' => 'Cottage cheese bowl', 'cal' => 200, 'protein' => 26],
                ['name' => 'Turkey bacon & egg wrap', 'cal' => 300, 'protein' => 34],
                ['name' => 'Oatmeal with protein powder', 'cal' => 270, 'protein' => 29],
                ['name' => 'Veggie frittata', 'cal' => 230, 'protein' => 27],
                ['name' => 'Almond butter rice cakes', 'cal' => 260, 'protein' => 18],
                ['name' => 'Chicken sausage scramble', 'cal' => 290, 'protein' => 36]
            ],
            'Lunch' => [
                ['name' => 'Grilled chicken salad', 'cal' => 350, 'protein' => 45],
                ['name' => 'Turkey lettuce wraps', 'cal' => 320, 'protein' => 38],
                ['name' => 'Tuna-stuffed avocado', 'cal' => 300, 'protein' => 40],
                ['name' => 'Vegetable lentil soup', 'cal' => 280, 'protein' => 24],
                ['name' => 'Cauliflower rice bowl', 'cal' => 290, 'protein' => 26],
                ['name' => 'Grilled shrimp skewers', 'cal' => 310, 'protein' => 42],
                ['name' => 'Zucchini noodle salad', 'cal' => 270, 'protein' => 22],
                ['name' => 'Egg salad lettuce cups', 'cal' => 250, 'protein' => 30],
                ['name' => 'Chicken vegetable soup', 'cal' => 240, 'protein' => 34],
                ['name' => 'Salmon quinoa bowl', 'cal' => 330, 'protein' => 44]
            ],
            'Dinner' => [
                ['name' => 'Grilled cod with broccoli', 'cal' => 380, 'protein' => 50],
                ['name' => 'Turkey meatball zoodles', 'cal' => 350, 'protein' => 45],
                ['name' => 'Chicken cauliflower rice', 'cal' => 320, 'protein' => 48],
                ['name' => 'Shrimp stir-fry', 'cal' => 300, 'protein' => 42],
                ['name' => 'Baked salmon asparagus', 'cal' => 370, 'protein' => 52],
                ['name' => 'Lean beef lettuce wraps', 'cal' => 330, 'protein' => 46],
                ['name' => 'Tofu vegetable curry', 'cal' => 290, 'protein' => 34],
                ['name' => 'Greek chicken salad', 'cal' => 310, 'protein' => 44],
                ['name' => 'Miso-glazed tuna steak', 'cal' => 340, 'protein' => 55],
                ['name' => 'Vegetable lentil stew', 'cal' => 280, 'protein' => 38]
            ]
        ],
        // ADDED: Similar structures for 'maintain' and 'gain' goals
        'maintain' => [
            'Breakfast' => [
                ['name' => 'Steak & eggs', 'cal' => 650, 'protein' => 55],
                ['name' => 'Protein oatmeal', 'cal' => 600, 'protein' => 45],
                ['name' => 'Breakfast burrito', 'cal' => 700, 'protein' => 48],
                ['name' => 'Peanut butter smoothie', 'cal' => 750, 'protein' => 50],
                ['name' => 'Chorizo hash', 'cal' => 680, 'protein' => 52],
                ['name' => 'Bagel with lox', 'cal' => 620, 'protein' => 40],
                ['name' => 'Protein French toast', 'cal' => 670, 'protein' => 58],
                ['name' => 'Beef breakfast skillet', 'cal' => 720, 'protein' => 60],
                ['name' => 'Mass gainer shake', 'cal' => 800, 'protein' => 65],
                ['name' => 'Pancakes with syrup', 'cal' => 750, 'protein' => 42],
                ['name' => 'Bacon egg cheese sandwich', 'cal' => 690, 'protein' => 50],
                ['name' => 'Greek yogurt granola', 'cal' => 650, 'protein' => 48],
                ['name' => 'Sausage breakfast bowl', 'cal' => 710, 'protein' => 55],
                ['name' => 'Protein waffles', 'cal' => 680, 'protein' => 60],
                ['name' => 'Steak breakfast tacos', 'cal' => 730, 'protein' => 62]
            ],
            'Lunch' => [
                ['name' => 'Beef burger + fries', 'cal' => 850, 'protein' => 65],
                ['name' => 'Chicken alfredo pasta', 'cal' => 900, 'protein' => 70],
                ['name' => 'Pulled pork sandwich', 'cal' => 800, 'protein' => 58],
                ['name' => 'BBQ ribs platter', 'cal' => 950, 'protein' => 75],
                ['name' => 'Cheesesteak hoagie', 'cal' => 880, 'protein' => 68],
                ['name' => 'Salmon rice bowl', 'cal' => 820, 'protein' => 60],
                ['name' => 'Lamb gyro platter', 'cal' => 780, 'protein' => 55],
                ['name' => 'Chicken fried steak', 'cal' => 920, 'protein' => 72],
                ['name' => 'Pork belly ramen', 'cal' => 850, 'protein' => 62],
                ['name' => 'Beef chili cheese fries', 'cal' => 970, 'protein' => 68],
                ['name' => 'Turkey club sandwich', 'cal' => 810, 'protein' => 58],
                ['name' => 'Cheese stuffed meatballs', 'cal' => 840, 'protein' => 70],
                ['name' => 'Chicken parmigiana', 'cal' => 790, 'protein' => 65],
                ['name' => 'Bison burger combo', 'cal' => 860, 'protein' => 72],
                ['name' => 'Poutine with steak', 'cal' => 890, 'protein' => 60]
            ],
            'Dinner' => [
                ['name' => 'Ribeye steak dinner', 'cal' => 950, 'protein' => 80],
                ['name' => 'BBQ chicken platter', 'cal' => 850, 'protein' => 70],
                ['name' => 'Lamb shank dinner', 'cal' => 900, 'protein' => 75],
                ['name' => 'Seafood paella', 'cal' => 880, 'protein' => 68],
                ['name' => 'Beef stroganoff', 'cal' => 820, 'protein' => 65],
                ['name' => 'Pork chop platter', 'cal' => 780, 'protein' => 72],
                ['name' => 'Duck confit meal', 'cal' => 920, 'protein' => 68],
                ['name' => 'Beef brisket plate', 'cal' => 970, 'protein' => 82],
                ['name' => 'Lobster mac & cheese', 'cal' => 890, 'protein' => 58],
                ['name' => 'Venison stew', 'cal' => 840, 'protein' => 75],
                ['name' => 'Prime rib dinner', 'cal' => 990, 'protein' => 85],
                ['name' => 'Chicken cordon bleu', 'cal' => 810, 'protein' => 70],
                ['name' => 'Beef Wellington', 'cal' => 950, 'protein' => 78],
                ['name' => 'BBQ pulled pork', 'cal' => 880, 'protein' => 65],
                ['name' => 'Surf & turf platter', 'cal' => 1000, 'protein' => 90]
            ]
        ],
        'gain' => [
            'Breakfast' => [
                // ... [add 10+ options] ...
                ['name' => 'Avocado toast with poached egg', 'cal' => 400, 'protein' => 18],
        ['name' => 'Greek yogurt parfait with granola', 'cal' => 380, 'protein' => 22],
        ['name' => 'Smoked salmon bagel', 'cal' => 420, 'protein' => 25],
        ['name' => 'Spinach and feta omelette', 'cal' => 350, 'protein' => 28],
        ['name' => 'Protein pancakes with berries', 'cal' => 400, 'protein' => 30],
        ['name' => 'Chia pudding with mixed nuts', 'cal' => 370, 'protein' => 15],
        ['name' => 'Whole grain waffles with almond butter', 'cal' => 430, 'protein' => 20],
        ['name' => 'Breakfast burrito with black beans', 'cal' => 450, 'protein' => 26],
        ['name' => 'Cottage cheese with pineapple', 'cal' => 320, 'protein' => 25],
        ['name' => 'Quinoa breakfast bowl with banana', 'cal' => 390, 'protein' => 18],
        ['name' => 'Egg white veggie scramble', 'cal' => 300, 'protein' => 30],
        ['name' => 'Peanut butter banana toast', 'cal' => 380, 'protein' => 15],
        ['name' => 'Oatmeal with walnuts and honey', 'cal' => 350, 'protein' => 12],
        ['name' => 'Breakfast smoothie with protein powder', 'cal' => 320, 'protein' => 35],
        ['name' => 'Turkish eggs with garlic yogurt', 'cal' => 400, 'protein' => 28]
            ],
            'Lunch' => [
                ['name' => 'Grilled chicken Caesar wrap', 'cal' => 500, 'protein' => 35],
        ['name' => 'Mediterranean grain bowl', 'cal' => 480, 'protein' => 22],
        ['name' => 'Turkey avocado club sandwich', 'cal' => 520, 'protein' => 40],
        ['name' => 'Sushi rolls with miso soup', 'cal' => 450, 'protein' => 25],
        ['name' => 'Quinoa salad with roasted veggies', 'cal' => 400, 'protein' => 18],
        ['name' => 'Grilled shrimp tacos', 'cal' => 470, 'protein' => 32],
        ['name' => 'Chicken pesto pasta', 'cal' => 550, 'protein' => 38],
        ['name' => 'Beef and vegetable stir-fry', 'cal' => 500, 'protein' => 42],
        ['name' => 'Falafel pita with tahini', 'cal' => 430, 'protein' => 20],
        ['name' => 'Tuna niçoise salad', 'cal' => 380, 'protein' => 35],
        ['name' => 'Vegetable lasagna', 'cal' => 420, 'protein' => 25],
        ['name' => 'Burrito bowl with brown rice', 'cal' => 480, 'protein' => 30],
        ['name' => 'Salmon poke bowl', 'cal' => 450, 'protein' => 40],
        ['name' => 'Chicken souvlaki with tzatziki', 'cal' => 500, 'protein' => 45],
        ['name' => 'Lentil and vegetable curry', 'cal' => 400, 'protein' => 22]
            ],
            'Dinner' => [
                ['name' => 'Grilled salmon with asparagus', 'cal' => 550, 'protein' => 48],
        ['name' => 'Beef tenderloin with mashed potatoes', 'cal' => 600, 'protein' => 55],
        ['name' => 'Vegetable paella', 'cal' => 480, 'protein' => 20],
        ['name' => 'Turkey meatballs with zoodles', 'cal' => 450, 'protein' => 42],
        ['name' => 'Miso-glazed cod with bok choy', 'cal' => 400, 'protein' => 38],
        ['name' => 'Chicken stir-fry with cashews', 'cal' => 500, 'protein' => 45],
        ['name' => 'Stuffed portobello mushrooms', 'cal' => 350, 'protein' => 25],
        ['name' => 'Lamb chops with mint yogurt', 'cal' => 550, 'protein' => 50],
        ['name' => 'Vegetable tempura with rice', 'cal' => 480, 'protein' => 18],
        ['name' => 'Pork tenderloin with apples', 'cal' => 520, 'protein' => 52],
        ['name' => 'Eggplant parmesan', 'cal' => 450, 'protein' => 28],
        ['name' => 'Seared scallops with quinoa', 'cal' => 400, 'protein' => 40],
        ['name' => 'Beef bourguignon', 'cal' => 580, 'protein' => 60],
        ['name' => 'Vegetable tofu curry', 'cal' => 380, 'protein' => 30],
        ['name' => 'Chicken piccata with pasta', 'cal' => 500, 'protein' => 48]
        
            ]
        ]
    ];

    // NEW: Randomize selection (3 items per meal time)
    $randomized_meals = [];
    foreach(['Breakfast', 'Lunch', 'Dinner'] as $meal_time) {
        $shuffled = $plans[$goal][$meal_time];
        shuffle($shuffled);
        $randomized_meals[$meal_time] = array_slice($shuffled, 0, 3);
    }

    return $randomized_meals;
}


function cfp_get_workout_plan($goal) {
    $workouts = [
        'loss' => [
                'Bodyweight squats: 3 sets of 12-15 reps',
                'Push-ups (knee version): 3 sets of 8-10 reps',
                'Plank: 3 sets of 20-30 seconds',
                'Glute bridges: 3 sets of 12-15 reps',
                'Bird-dog: 3 sets of 10 reps per side',
                'Wall sit: 3 sets of 30-45 seconds'
        ],
        'maintain' => [
            'Jump squats: 4 sets of 15-20 reps',
                'Push-up variations: 4 sets of 10-12 reps',
                'Mountain climbers: 4 sets of 30 seconds',
                'Lunges: 4 sets of 10 reps per leg',
                'Superman holds: 4 sets of 30 seconds',
                'Burpees: 4 sets of 10-12 reps'
        ],
        'gain' => [
            'Pistol squats: 5 sets of 8-10 reps per leg',
                'Decline push-ups: 5 sets of 12-15 reps',
                'Pull-ups (if available): 5 sets of max reps',
                'Jump lunges: 5 sets of 15 reps per leg',
                'Handstand push-ups: 5 sets of 5-8 reps',
                'Plank to push-up: 5 sets of 10 reps per side'
        ]
    ];
    return $workouts[$goal];
}