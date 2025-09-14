<?php
// require_once '../includes/config.php';

/**
 * Sync the last donation date for a student based on the donations table.
 *
 * @param PDO $pdo The PDO database connection.
 * @param int $donor_id The ID of the donor whose last donation date should be synced.
 * @return bool True on success, false on failure.
 */
function syncLastDonationDate($pdo, $donor_id) {
    try {
        // Prepare the update statement
        $stmt = $pdo->prepare("
            UPDATE students 
            SET last_donation_date = (
                SELECT MAX(donation_date) FROM donations WHERE donor_id = ?
            )
            WHERE id = ?
        ");
        
        // Execute with donor_id for both placeholders
        return $stmt->execute([$donor_id, $donor_id]);
        
    } catch (PDOException $e) {
        error_log("Error syncing last donation date: " . $e->getMessage());
        return false;
    }
}
