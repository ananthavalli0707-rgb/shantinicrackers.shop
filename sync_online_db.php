<?php
require 'config/db.php';

echo "<pre>";

// 1. Add Columns to Database if they don't exist
try {
    $pdo->exec("ALTER TABLE categories ADD COLUMN tamil_name VARCHAR(255) DEFAULT NULL;");
    echo "Added 'tamil_name' column to categories.\n";
} catch (Exception $e) {
    echo "categories 'tamil_name' column already exists.\n";
}

try {
    $pdo->exec("ALTER TABLE products ADD COLUMN tamil_name VARCHAR(255) DEFAULT NULL;");
    echo "Added 'tamil_name' column to products.\n";
} catch (Exception $e) {
    echo "products 'tamil_name' column already exists.\n";
}

try {
    $pdo->exec("ALTER TABLE inquiries ADD COLUMN is_paid BOOLEAN DEFAULT FALSE, ADD COLUMN is_dispatched BOOLEAN DEFAULT FALSE, ADD COLUMN is_delivered BOOLEAN DEFAULT FALSE;");
    echo "Added toggle columns to inquiries.\n";
} catch (Exception $e) {
    echo "Toggle columns in inquiries already exist.\n";
}

// 2. Perform Translations
$categories = [
    'Single Sound Crackers' => 'ஒற்றை வெடி',
    'Flower Pots' => 'பூச்சட்டி',
    'Chakkars' => 'சக்கரம்',
    'Sparklers' => 'கம்பி மத்தாப்பு',
    'Twinkling Stars' => 'சாட்டை',
    'Pencil' => 'பென்சில்',
    'Bomb' => 'பாம்',
    'Fancy Fountain' => 'பேன்சி பவுண்டன்',
    'Sky Shot' => 'ஸ்கை ஷாட்',
    'Rockets' => 'ராக்கெட்',
    'Bijili' => 'பிஜிலி',
    'Matches' => 'தீப்பெட்டி',
    'Garlands' => 'சரவெடி',
    'Repeater' => 'ரிப்பீட்டர்',
    'Comet' => 'காமெட்',
    'Gift Box' => 'கிப்ட் பாக்ஸ்',
    'Colour Matches' => 'கலர் தீப்பெட்டி',
    'Kids Special Crackers' => 'குழந்தைகள் வெடி',
    'New Arrival' => 'புதிய வரவு'
];

foreach ($categories as $en => $ta) {
    $stmt = $pdo->prepare('UPDATE categories SET tamil_name = :ta WHERE name = :en');
    $stmt->execute(['ta' => $ta, 'en' => $en]);
}
echo "Categories updated.\n";

$products = [
    '2¾" Bird' => '2¾" குருவி',
    '3½" Laxmi' => '3½" லக்ஷ்மி',
    '4" Laxmi' => '4" லக்ஷ்மி',
    '4" Gold Laxmi' => '4" கோல்டு லக்ஷ்மி',
    '4" Gold (25 Pcs)' => '4" கோல்டு (25 Pcs)',
    '5" Mega Deluxe' => '5" மெகா டீலக்ஸ்',
    'Two Sound' => 'டூ சவுண்ட்',
    'Flower Pots Small' => 'சிறிய பூச்சட்டி',
    'Flower Pots Big' => 'பெரிய பூச்சட்டி',
    'Flower Pots Special' => 'ஸ்பெஷல் பூச்சட்டி',
    'Flower Pots Ashoka' => 'அசோகா பூச்சட்டி',
    'Flower Pots Color' => 'கலர் பூச்சட்டி',
    'Flower Pots Deluxe' => 'டீலக்ஸ் பூச்சட்டி',
    'Colour Koti' => 'கலர் கோட்டி',
    'Tri Colour (5 Pcs)' => 'ட்ரை கலர்',
    'Ground Chakkar Big' => 'பெரிய தரை சக்கரம்',
    'Ground Chakkar Ashoka' => 'அசோகா தரை சக்கரம்',
    'Ground Chakkar Special' => 'ஸ்பெஷல் தரை சக்கரம்',
    'Ground Chakkar Deluxe' => 'டீலக்ஸ் தரை சக்கரம்',
    'Wire Chakkar' => 'கம்பி சக்கரம்',
    'Whistling Chakkar' => 'விசில் சக்கரம்',
    'Magic Chakkar (3 Pcs)' => 'மேஜிக் சக்கரம் (3 Pcs)',
    '10 cm Sparklers' => '10 செ.மீ கம்பி மத்தாப்பு',
    '12 cm Sparklers' => '12 செ.மீ கம்பி மத்தாப்பு',
    '15 cm Sparklers' => '15 செ.மீ கம்பி மத்தாப்பு',
    '15 cm Red' => '15 செ.மீ சிவப்பு',
    '15 cm Green' => '15 செ.மீ பச்சை',
    '30 cm Sparklers' => '30 செ.மீ கம்பி மத்தாப்பு',
    '30 cm Red' => '30 செ.மீ சிவப்பு',
    '30 cm Green' => '30 செ.மீ பச்சை',
    '30 cm Colour Sparklers' => '30 செ.மீ கலர்',
    '50 cm Sparklers' => '50 செ.மீ கம்பி மத்தாப்பு',
    '1½\' Twinkling Star' => '1½\' சாட்டை',
    '3\' Twinkling Star' => '3\' சாட்டை',
    '7" Pencil' => '7" பென்சில்',
    '10" Pencil' => '10" பென்சில்',
    'Bullet Bomb' => 'புல்லட் பாம்',
    'Hydro Bomb' => 'ஹைட்ரோ பாம்',
    'King of King (1 Pcs)' => 'கிங் ஆப் கிங்',
    'Magic Peacock (1 Pcs)' => 'மேஜிக் மயில்',
    'Helicopter (1 Pcs)' => 'ஹெலிகாப்டர்',
    'Drone (1 Pcs)' => 'ட்ரோன்',
    'Chotta Bheem' => 'சோட்டா பீம்',
    'Baby Rockets' => 'பேபி ராக்கெட்',
    'Lunik Rockets' => 'லூனிக் ராக்கெட்',
    'Red Bijili (50 Pcs)' => 'சிவப்பு பிஜிலி (50 Pcs)',
    'Stripped Bijili (50 Pcs)' => 'கோடு பிஜிலி (50 Pcs)',
    'Stripped Bijili (100 Pcs)' => 'கோடு பிஜிலி (100 Pcs)',
    'One Set' => 'ஒன் செட்',
    '28 Chorsa' => '28 சோர்சா',
    '100 Wala' => '100 வாலா',
    '200 Wala' => '200 வாலா',
    '600 Wala' => '600 வாலா',
    '1000 Wala' => '1000 வாலா',
    '2000 Wala' => '2000 வாலா',
    '5000 Wala' => '5000 வாலா',
    '10000 Wala' => '10000 வாலா',
    'Small (12 Shots)' => 'சிறிய ரிப்பீட்டர் (12 Shots)',
    'Medium (12 Shots)' => 'நடுத்தர ரிப்பீட்டர் (12 Shots)',
    'Large (12 Shots)' => 'பெரிய ரிப்பீட்டர் (12 Shots)',
    '30 Shots' => '30 ஷாட்',
    '60 Shots' => '60 ஷாட்',
    '120 Shots' => '120 ஷாட்',
    '240 Shots' => '240 ஷாட்',
    'Star (1" Comet)' => 'ஸ்டார் (1" காமெட்)',
    'Super (1.5" Comet)' => 'சூப்பர் (1.5" காமெட்)',
    'Deluxe (2" Comet)' => 'டீலக்ஸ் (2" காமெட்)',
    'Box A' => 'பாக்ஸ் A',
    'Box B' => 'பாக்ஸ் B',
    'Standard' => 'ஸ்டாண்டர்ட்',
    'Deluxe' => 'டீலக்ஸ்',
    'Pop Pop (Magic Snaps)' => 'பாப் பாப்',
    'Serpent (Black Snake)' => 'கருப்பு பாம்பு',
    'Sparkle Fountain' => 'ஸ்பார்க்கிள் பவுண்டன்',
    'Icone' => 'ஐகான்',
    'Sword' => 'கத்தி',
    'Pambaram' => 'பம்பரம்'
];

foreach ($products as $en => $ta) {
    $stmt = $pdo->prepare('UPDATE products SET tamil_name = :ta WHERE name = :en');
    $stmt->execute(['ta' => $ta, 'en' => $en]);
}
echo "Products updated.\n";
echo "SUCCESS: Online Database synced completely!\n";
echo "</pre>";
