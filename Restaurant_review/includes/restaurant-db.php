<?php

function normalizeDateTimeValue($value)
{
    $trimmed = trim((string) $value);
    if ($trimmed === '') {
        return false;
    }

    $timestamp = strtotime($trimmed);
    if ($timestamp === false) {
        return false;
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function fetchAllAssocFromStatement($stmt)
{
    $meta = $stmt->result_metadata();
    if (!$meta) {
        return [];
    }

    $fields = [];
    $row = [];
    $bindParams = [];

    while ($field = $meta->fetch_field()) {
        $fieldName = $field->name;
        $fields[] = $fieldName;
        $row[$fieldName] = null;
        $bindParams[] = &$row[$fieldName];
    }

    call_user_func_array([$stmt, 'bind_result'], $bindParams);

    $rows = [];
    while ($stmt->fetch()) {
        $current = [];
        foreach ($fields as $fieldName) {
            $current[$fieldName] = $row[$fieldName];
        }
        $rows[] = $current;
    }

    $meta->free();
    return $rows;
}

function fetchOneAssocFromStatement($stmt)
{
    $rows = fetchAllAssocFromStatement($stmt);
    return $rows[0] ?? null;
}

function getDatabaseConnection(&$errorMessage)
{
    $errorMessage = '';

    if (!class_exists('mysqli')) {
        $errorMessage = 'MySQLi extension is not available on the server.';
        return null;
    }

    $configPaths = [
        __DIR__ . '/../db-config.ini',
        dirname(__DIR__) . '/db-config.ini',
        '/var/www/private/db-config.ini'
    ];

    $config = false;
    $checkedPaths = [];

    foreach ($configPaths as $path) {
        $checkedPaths[] = $path;
        if (is_readable($path)) {
            $config = @parse_ini_file($path);
            if ($config !== false) {
                break;
            }
        }
    }

    if ($config === false) {
        $errorMessage = 'Failed to read database config file. Checked paths: ' . implode(', ', $checkedPaths);
        return null;
    }

    if (!isset($config['servername'], $config['username'], $config['password'], $config['dbname'])) {
        $errorMessage = 'Database config file is missing one or more required keys.';
        return null;
    }

    try {
        $connection = @new mysqli(
            $config['servername'],
            $config['username'],
            $config['password'],
            $config['dbname']
        );

        if ($connection->connect_error) {
            $errorMessage = 'Database connection failed: ' . $connection->connect_error;
            return null;
        }
    } catch (Throwable $e) {
        $errorMessage = 'Database connection failed: ' . $e->getMessage();
        return null;
    }

    return $connection;
}

function ownerIdExists($connection, $ownerId, &$errorMessage)
{
    $errorMessage = '';
    $stmt = $connection->prepare('SELECT 1 FROM users WHERE idusers = ? LIMIT 1');

    if (!$stmt) {
        $errorMessage = 'Failed to prepare OwnerId check: ' . $connection->error;
        return false;
    }

    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $row = fetchOneAssocFromStatement($stmt);
    $exists = $row !== null;
    $stmt->close();

    if (!$exists) {
        $errorMessage = 'OwnerId does not exist in users table.';
    }

    return $exists;
}

function insertRestaurantRecord($connection, $restaurantData, &$errorMessage)
{
    $errorMessage = '';
    $openingHours = normalizeDateTimeValue($restaurantData['OpeningHours']);
    $closingHours = normalizeDateTimeValue($restaurantData['ClosingHours']);

    if ($openingHours === false) {
        $errorMessage = 'OpeningHours must be a valid date and time.';
        return false;
    }

    if ($closingHours === false) {
        $errorMessage = 'ClosingHours must be a valid date and time.';
        return false;
    }

    $stmt = $connection->prepare(
        'INSERT INTO Restaurants (RestaurantName, OwnerId, Address, PhoneNum, CusineType, OpeningHours, ClosingHours, OpeningDays, PriceRange) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$stmt) {
        $errorMessage = 'Failed to prepare restaurant insert: ' . $connection->error;
        return false;
    }

    $stmt->bind_param(
        'sisssssss',
        $restaurantData['RestaurantName'],
        $restaurantData['OwnerId'],
        $restaurantData['Address'],
        $restaurantData['PhoneNum'],
        $restaurantData['CusineType'],
        $openingHours,
        $closingHours,
        $restaurantData['OpeningDays'],
        $restaurantData['PriceRange']
    );

    $ok = $stmt->execute();

    if (!$ok) {
        $errorMessage = 'Failed to insert restaurant: ' . $stmt->error;
    }

    $insertedId = $ok ? (int) $connection->insert_id : 0;
    $stmt->close();

    return $ok ? $insertedId : false;
}

function updateRestaurantRecord($connection, $restaurantData, &$errorMessage)
{
    $errorMessage = '';
    $openingHours = normalizeDateTimeValue($restaurantData['OpeningHours']);
    $closingHours = normalizeDateTimeValue($restaurantData['ClosingHours']);

    if ($openingHours === false) {
        $errorMessage = 'OpeningHours must be a valid date and time.';
        return false;
    }

    if ($closingHours === false) {
        $errorMessage = 'ClosingHours must be a valid date and time.';
        return false;
    }

    $checkStmt = $connection->prepare('SELECT 1 FROM Restaurants WHERE idRestaurants = ? LIMIT 1');
    if (!$checkStmt) {
        $errorMessage = 'Failed to prepare restaurant existence check: ' . $connection->error;
        return false;
    }

    $checkStmt->bind_param('i', $restaurantData['idRestaurants']);
    $checkStmt->execute();
    $checkRow = fetchOneAssocFromStatement($checkStmt);
    $exists = $checkRow !== null;
    $checkStmt->close();

    if (!$exists) {
        $errorMessage = 'Restaurant ID was not found.';
        return false;
    }

    $stmt = $connection->prepare(
        'UPDATE Restaurants SET RestaurantName = ?, OwnerId = ?, Address = ?, PhoneNum = ?, CusineType = ?, OpeningHours = ?, ClosingHours = ?, OpeningDays = ?, PriceRange = ? WHERE idRestaurants = ?'
    );

    if (!$stmt) {
        $errorMessage = 'Failed to prepare restaurant update: ' . $connection->error;
        return false;
    }

    $stmt->bind_param(
        'sisssssssi',
        $restaurantData['RestaurantName'],
        $restaurantData['OwnerId'],
        $restaurantData['Address'],
        $restaurantData['PhoneNum'],
        $restaurantData['CusineType'],
        $openingHours,
        $closingHours,
        $restaurantData['OpeningDays'],
        $restaurantData['PriceRange'],
        $restaurantData['idRestaurants']
    );

    $ok = $stmt->execute();

    if (!$ok) {
        $errorMessage = 'Failed to update restaurant: ' . $stmt->error;
    }

    $stmt->close();

    return $ok;
}

function getRestaurantById($connection, $restaurantId, &$errorMessage)
{
    $errorMessage = '';
    $stmt = $connection->prepare(
        'SELECT r.idRestaurants, r.RestaurantName, r.OwnerId, r.Address, r.PhoneNum, r.CusineType, r.OpeningHours, r.ClosingHours, r.OpeningDays, r.PriceRange, img.ImageUrl
            FROM Restaurants r
         LEFT JOIN restaurantimages img ON img.idRestaurants = r.idRestaurants
         WHERE r.idRestaurants = ?
         ORDER BY img.idRestaurantImages ASC
         LIMIT 1'
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

function getFirstRestaurantId($connection, &$errorMessage)
{
    $errorMessage = '';
    $result = $connection->query('SELECT idRestaurants FROM Restaurants ORDER BY idRestaurants ASC LIMIT 1');

    if (!$result) {
        $errorMessage = 'Failed to fetch default restaurant: ' . $connection->error;
        return null;
    }

    $row = $result->fetch_assoc();
    $result->free();

    if (!$row) {
        return null;
    }

    return (int) $row['idRestaurants'];
}

function getRestaurantReviews($connection, $restaurantId, &$errorMessage)
{
    $errorMessage = '';
    $stmt = $connection->prepare(
        'SELECT rv.idReview, rv.Rating, rv.Comments, rv.ReviewDate, u.name AS ReviewerName
         FROM reviews rv
         INNER JOIN users u ON u.idusers = rv.UserId
         WHERE rv.RestaurantID = ?
         ORDER BY rv.ReviewDate DESC'
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

function insertReviewRecord($connection, $reviewData, &$errorMessage)
{
    $errorMessage = '';
    $stmt = $connection->prepare(
        'INSERT INTO reviews (UserId, RestaurantID, Rating, Comments, ReviewDate) VALUES (?, ?, ?, ?, NOW())'
    );

    if (!$stmt) {
        $errorMessage = 'Failed to prepare review insert: ' . $connection->error;
        return false;
    }

    $stmt->bind_param(
        'iiis',
        $reviewData['UserId'],
        $reviewData['RestaurantID'],
        $reviewData['Rating'],
        $reviewData['Comments']
    );

    $ok = $stmt->execute();
    if (!$ok) {
        $errorMessage = 'Failed to submit review: ' . $stmt->error;
    }
    $stmt->close();

    return $ok;
}
