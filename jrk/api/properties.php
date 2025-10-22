<?php
/**
 * Get Properties API Endpoint
 * GET /api/properties
 */

requireAuth();

$properties = getAccessibleProperties();

// Get contacts for each property
foreach ($properties as &$property) {
    $contacts = Database::query(
        "SELECT name, phone, email FROM property_contacts WHERE property_id = ? ORDER BY position",
        [$property['id']]
    );
    $property['contacts'] = $contacts;
}

jsonResponse(['properties' => $properties]);
