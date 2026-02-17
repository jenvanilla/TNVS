<?php
/**
 * Seed script — inserts 5 sample RFQs + items into the proc database.
 * Run once, then delete this file.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$pdo = db('proc');

$rfqs = [
    ['RFQ-2026-0201', 'Office Supplies & Stationery',              '2026-03-15 17:00:00', 'PHP'],
    ['RFQ-2026-0202', 'IT Equipment - Laptops & Monitors',         '2026-03-20 17:00:00', 'PHP'],
    ['RFQ-2026-0203', 'Janitorial & Cleaning Supplies',            '2026-03-10 17:00:00', 'PHP'],
    ['RFQ-2026-0204', 'Construction Materials - Phase 2 Renovation','2026-04-01 17:00:00', 'PHP'],
    ['RFQ-2026-0205', 'Catering Services for Annual Event',        '2026-03-25 17:00:00', 'PHP'],
];

$allItems = [
    'RFQ-2026-0201' => [
        [1, 'Bond Paper A4',    '80gsm, white, 500 sheets/ream', 50,  'reams'],
        [2, 'Ballpoint Pens',   'Blue ink, 0.7mm',               200, 'pcs'],
        [3, 'Manila Folders',   'Legal size, assorted colors',    100, 'pcs'],
    ],
    'RFQ-2026-0202' => [
        [1, 'Laptop',              '14-inch, i5 12th Gen, 16GB RAM, 512GB SSD', 10, 'units'],
        [2, 'Monitor',             '24-inch IPS, Full HD, HDMI',                15, 'units'],
        [3, 'Keyboard & Mouse Set','Wireless, USB receiver',                    15, 'sets'],
        [4, 'Laptop Bag',          '14-inch compatible, padded',                10, 'pcs'],
    ],
    'RFQ-2026-0203' => [
        [1, 'Floor Cleaner', 'Multi-surface, 5L gallon',  30,  'gallons'],
        [2, 'Trash Bags',    'Black, heavy duty, XL',     500, 'pcs'],
    ],
    'RFQ-2026-0204' => [
        [1, 'Portland Cement', 'Type I, 40kg bags',              200, 'bags'],
        [2, 'Steel Bars',      '10mm deformed bar, 6m',          150, 'pcs'],
        [3, 'Plywood',         '3/4 inch marine plywood, 4x8ft', 80,  'sheets'],
        [4, 'Paint',           'Latex white, 4L cans',           60,  'cans'],
        [5, 'Electrical Wire', 'THHN 3.5mm, stranded',           500, 'meters'],
    ],
    'RFQ-2026-0205' => [
        [1, 'Buffet Catering',   'Filipino menu, 150 pax, lunch',       1, 'event'],
        [2, 'Dessert Station',   'Assorted pastries & fruit, 150 pax',  1, 'event'],
        [3, 'Beverage Package',  'Juice, water, coffee for 150 pax',    1, 'event'],
    ],
];

$ins     = $pdo->prepare('INSERT INTO rfqs (rfq_no, title, status, due_at, currency) VALUES (?,?,?,?,?)');
$insItem = $pdo->prepare('INSERT INTO rfq_items (rfq_id, line_no, item, specs, qty, uom) VALUES (?,?,?,?,?,?)');

foreach ($rfqs as $r) {
    $ins->execute([$r[0], $r[1], 'sent', $r[2], $r[3]]);
    $rfqId = (int)$pdo->lastInsertId();
    echo "Inserted RFQ {$r[0]} (id={$rfqId})\n";

    $items = $allItems[$r[0]] ?? [];
    foreach ($items as $item) {
        $insItem->execute([$rfqId, $item[0], $item[1], $item[2], $item[3], $item[4]]);
    }
    echo "  -> " . count($items) . " items added\n";
}

echo "\nDone! You can now visit vendor_portal/ to see them.\n";
echo "Delete this file (seed_rfqs.php) when you're done.\n";
