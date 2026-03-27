<?php
// ── Helpers ───────────────────────────────────────────────────────────────────
function fetchAllAssocFromStatement($stmt)
{
    $result = $stmt->get_result();
    if (!$result) return [];
    $rows = [];
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
    $result->free();
    return $rows;
}

function fetchOneAssocFromStatement($stmt)
{
    $rows = fetchAllAssocFromStatement($stmt);
    return $rows[0] ?? null;
}

// ── Database connection ───────────────────────────────────────────────────────
// Reads credentials from db-config.ini located one level above this file.
function getDatabaseConnection(&$errorMessage)
{
    $errorMessage = '';
    if (!class_exists('mysqli')) {
        $errorMessage = 'MySQLi extension is not available.';
        return null;
    }
    $configPaths = [
        __DIR__ . '/../db-config.ini',
        dirname(__DIR__) . '/db-config.ini',
        '/var/www/private/db-config.ini'
    ];
    $config       = false;
    $checkedPaths = [];
    foreach ($configPaths as $path) {
        $checkedPaths[] = $path;
        if (is_readable($path)) {
            $config = @parse_ini_file($path);
            if ($config !== false) break;
        }
    }
    if ($config === false) {
        $errorMessage = 'Failed to read db-config.ini. Checked: ' . implode(', ', $checkedPaths);
        return null;
    }
    if (!isset($config['servername'], $config['username'], $config['password'], $config['dbname'])) {
        $errorMessage = 'db-config.ini is missing required keys.';
        return null;
    }
    try {
        $conn = @new mysqli(
            $config['servername'],
            $config['username'],
            $config['password'],
            $config['dbname']
        );
        if ($conn->connect_error) {
            $errorMessage = 'DB connection failed: ' . $conn->connect_error;
            return null;
        }
    } catch (Throwable $e) {
        $errorMessage = 'DB connection failed: ' . $e->getMessage();
        return null;
    }
    return $conn;
}

// ── Check if an owner exists in users table ───────────────────────────────────
// ERD: users(idusers INT)
function ownerIdExists($connection, $ownerId, &$errorMessage)
{
    $errorMessage = '';
    $stmt = $connection->prepare('SELECT 1 FROM users WHERE idusers = ? LIMIT 1');
    if (!$stmt) {
        $errorMessage = 'Failed to prepare OwnerID check: ' . $connection->error;
        return false;
    }
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $row    = fetchOneAssocFromStatement($stmt);
    $exists = $row !== null;
    $stmt->close();
    if (!$exists) {
        $errorMessage = 'OwnerID does not exist in users table.';
    }
    return $exists;
}

// ── Insert a new restaurant ───────────────────────────────────────────────────
// ERD: Restaurants(idRestaurants PK, RestaurantName, OwnerId FK, Address,
//                  PhoneNum, CusineType, OpeningHours, PriceRange,
//                  ClosingHours, OpeningDays)
function insertRestaurantRecord($connection, $restaurantData, &$errorMessage)
{
    $errorMessage = '';
    $stmt = $connection->prepare(
        'INSERT INTO Restaurants
            (RestaurantName, OwnerId, Address, PhoneNum, CusineType,
             OpeningHours, ClosingHours, OpeningDays, PriceRange)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        $errorMessage = 'Failed to prepare restaurant insert: ' . $connection->error;
        return false;
    }
    $name       = $restaurantData['RestaurantName'] ?? '';
    $ownerId    = (int) ($restaurantData['OwnerId'] ?? 0);
    $address    = $restaurantData['Address']        ?? '';
    $phone      = $restaurantData['PhoneNum']       ?? '';
    $cuisine    = $restaurantData['CusineType']     ?? '';
    $openHours  = $restaurantData['OpeningHours']   ?? '';
    $closeHours = $restaurantData['ClosingHours']   ?? '';
    $openDays   = $restaurantData['OpeningDays']    ?? '';
    $price      = $restaurantData['PriceRange']     ?? '';
    // 9 params: s i s s s s s s s
    $stmt->bind_param('sisssssss', $name, $ownerId, $address, $phone, $cuisine, $openHours, $closeHours, $openDays, $price);
    $ok = $stmt->execute();
    if (!$ok) {
        $errorMessage = 'Failed to insert restaurant: ' . $stmt->error;
    }
    $insertedId = $ok ? (int) $connection->insert_id : 0;
    $stmt->close();
    return $ok ? $insertedId : false;
}

// ── Update an existing restaurant ─────────────────────────────────────────────
function updateRestaurantRecord($connection, $restaurantData, &$errorMessage)
{
    $errorMessage = '';
    $restId = (int) ($restaurantData['idRestaurants'] ?? 0);

    // Verify restaurant exists
    $checkStmt = $connection->prepare(
        'SELECT 1 FROM Restaurants WHERE idRestaurants = ? LIMIT 1'
    );
    if (!$checkStmt) {
        $errorMessage = 'Failed to prepare existence check: ' . $connection->error;
        return false;
    }
    $checkStmt->bind_param('i', $restId);
    $checkStmt->execute();
    $checkRow = fetchOneAssocFromStatement($checkStmt);
    $checkStmt->close();
    if ($checkRow === null) {
        $errorMessage = 'Restaurant ID not found.';
        return false;
    }

    $stmt = $connection->prepare(
        'UPDATE Restaurants
         SET    RestaurantName = ?,
                OwnerId        = ?,
                Address        = ?,
                PhoneNum       = ?,
                CusineType     = ?,
                OpeningHours   = ?,
                ClosingHours   = ?,
                OpeningDays    = ?,
                PriceRange     = ?
         WHERE  idRestaurants  = ?'
    );
    if (!$stmt) {
        $errorMessage = 'Failed to prepare restaurant update: ' . $connection->error;
        return false;
    }
    $name       = $restaurantData['RestaurantName'] ?? '';
    $ownerId    = (int) ($restaurantData['OwnerId'] ?? 0);
    $address    = $restaurantData['Address']        ?? '';
    $phone      = $restaurantData['PhoneNum']       ?? '';
    $cuisine    = $restaurantData['CusineType']     ?? '';
    $openHours  = $restaurantData['OpeningHours']   ?? '';
    $closeHours = $restaurantData['ClosingHours']   ?? '';
    $openDays   = $restaurantData['OpeningDays']    ?? '';
    $price      = $restaurantData['PriceRange']     ?? '';
    // 10 params: s i s s s s s s s i
    $stmt->bind_param('sisssssssi', $name, $ownerId, $address, $phone, $cuisine, $openHours, $closeHours, $openDays, $price, $restId);
    $ok = $stmt->execute();
    if (!$ok) {
        $errorMessage = 'Failed to update restaurant: ' . $stmt->error;
    }
    $stmt->close();
    return $ok;
}

// ── Get a single restaurant by idRestaurants ──────────────────────────────────
// ERD: Restaurants(idRestaurants, RestaurantName, OwnerId, Address, PhoneNum,
//                  CusineType, OpeningHours, PriceRange, ClosingHours, OpeningDays)
//      RestaurantImages(idRestaurantImages, idRestaurants FK, ImageUrl)
function getRestaurantById($connection, $restaurantId, &$errorMessage)
{
    $errorMessage = '';
    $stmt = $connection->prepare(
        'SELECT r.idRestaurants,
                r.RestaurantName,
                r.OwnerId,
                r.Address,
                r.PhoneNum,
                r.CusineType,
                r.OpeningHours,
                r.ClosingHours,
                r.OpeningDays,
                r.PriceRange,
                img.ImageUrl
         FROM   Restaurants r
         LEFT   JOIN RestaurantImages img ON img.idRestaurants = r.idRestaurants
         WHERE  r.idRestaurants = ?
         ORDER  BY img.idRestaurantImages ASC
         LIMIT  1'
    );
    if (!$stmt) {
        $errorMessage = 'Failed to prepare restaurant lookup: ' . $connection->error;
        return null;
    }
    $stmt->bind_param('i', $restaurantId);
    $stmt->execute();
    $row = fetchOneAssocFromStatement($stmt);
    $stmt->close();
    return $row ?: null;
}

// ── Get the first restaurant ID ───────────────────────────────────────────────
function getFirstRestaurantId($connection, &$errorMessage)
{
    $errorMessage = '';
    $result = $connection->query(
        'SELECT idRestaurants FROM Restaurants ORDER BY idRestaurants ASC LIMIT 1'
    );
    if (!$result) {
        $errorMessage = 'Failed to fetch default restaurant: ' . $connection->error;
        return null;
    }
    $row = $result->fetch_assoc();
    $result->free();
    return $row ? (int) $row['idRestaurants'] : null;
}

// ── Get all restaurants owned by a user ──────────────────────────────────────
// ERD: Restaurants(OwnerId FK → users.idusers)
function getRestaurantsByOwnerId($connection, $ownerId, &$errorMessage)
{
    $errorMessage = '';
    $stmt = $connection->prepare(
        'SELECT idRestaurants, RestaurantName
         FROM   Restaurants
         WHERE  OwnerId = ?
         ORDER  BY RestaurantName ASC'
    );
    if (!$stmt) {
        $errorMessage = 'Failed to prepare owner restaurants lookup: ' . $connection->error;
        return [];
    }
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $rows = fetchAllAssocFromStatement($stmt);
    $stmt->close();
    return $rows;
}

// ── Get all reviews for a restaurant ─────────────────────────────────────────
// ERD: Reviews(idReview PK, UserId FK, RestaurantID FK, Rating, Comments, ReviewDate)
//      users(idusers PK, name, email)
function getRestaurantReviews($connection, $restaurantId, &$errorMessage)
{
    $errorMessage = '';
    $stmt = $connection->prepare(
        'SELECT rv.idReview,
                rv.UserId,
                rv.Rating,
                rv.Comments,
                rv.ReviewDate,
                u.name  AS reviewer_name,
                u.email AS reviewer_email
         FROM   Reviews rv
         LEFT   JOIN users u ON u.idusers = rv.UserId
         WHERE  rv.RestaurantID = ?
         ORDER  BY rv.ReviewDate DESC'
    );
    if (!$stmt) {
        $errorMessage = 'Failed to prepare review lookup: ' . $connection->error;
        return [];
    }
    $stmt->bind_param('i', $restaurantId);
    $stmt->execute();
    $reviews = fetchAllAssocFromStatement($stmt);
    $stmt->close();
    return $reviews;
}

// ── Insert a review ───────────────────────────────────────────────────────────
// ERD: Reviews(UserId FK, RestaurantID FK, Rating, Comments, ReviewDate)
function insertReviewRecord($connection, $reviewData, &$errorMessage)
{
    $errorMessage = '';
    $stmt = $connection->prepare(
        'INSERT INTO Reviews (UserId, RestaurantID, Rating, Comments, ReviewDate)
         VALUES (?, ?, ?, ?, NOW())'
    );
    if (!$stmt) {
        $errorMessage = 'Failed to prepare review insert: ' . $connection->error;
        return false;
    }
    $userId    = (int) ($reviewData['UserId']       ?? 0);
    $restId    = (int) ($reviewData['RestaurantID'] ?? 0);
    $rating    = $reviewData['Rating']              ?? '';
    $comments  = $reviewData['Comments']            ?? '';
    $stmt->bind_param('iiss', $userId, $restId, $rating, $comments);
    $ok = $stmt->execute();
    if (!$ok) {
        $errorMessage = 'Failed to submit review: ' . $stmt->error;
    }
    $stmt->close();
    return $ok;
}

// ── Search restaurants by keyword ──────────────────────────────────────────────
// Searches by restaurant name, cuisine type, and address (case-insensitive)
function searchRestaurants($connection, $searchKeyword, &$errorMessage)
{
    $errorMessage = '';
    if (trim($searchKeyword) === '') {
        // Return all restaurants if search is empty
        $result = $connection->query(
            'SELECT idRestaurants, RestaurantName, Address, CusineType, PriceRange
             FROM Restaurants
             ORDER BY RestaurantName ASC'
        );
        if (!$result) {
            $errorMessage = 'Failed to fetch restaurants: ' . $connection->error;
            return [];
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
        return $rows;
    }
    
    $stmt = $connection->prepare(
        'SELECT idRestaurants, RestaurantName, Address, CusineType, PriceRange
         FROM Restaurants
         WHERE LOWER(RestaurantName) LIKE LOWER(?) OR LOWER(CusineType) LIKE LOWER(?) OR LOWER(Address) LIKE LOWER(?)
         ORDER BY RestaurantName ASC'
    );
    if (!$stmt) {
        $errorMessage = 'Failed to prepare search query: ' . $connection->error;
        return [];
    }
    $searchTerm = '%' . $searchKeyword . '%';
    $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $rows = fetchAllAssocFromStatement($stmt);
    $stmt->close();
    return $rows;
}

// ── Insert a restaurant image ─────────────────────────────────────────────────
// ERD: RestaurantImages(idRestaurantImages PK, idRestaurants FK, ImageUrl)
function insertRestaurantImage($connection, $restaurantId, $imageUrl, &$errorMessage)
{
    $errorMessage = '';
    $stmt = $connection->prepare(
        'INSERT INTO RestaurantImages (idRestaurants, ImageUrl) VALUES (?, ?)'
    );
    if (!$stmt) {
        $errorMessage = 'Failed to prepare image insert: ' . $connection->error;
        return false;
    }
    $stmt->bind_param('is', $restaurantId, $imageUrl);
    $ok = $stmt->execute();
    if (!$ok) {
        $errorMessage = 'Failed to insert image: ' . $stmt->error;
    }
    $stmt->close();
    return $ok;
}
