<?php
include('../../routes/db_conn.php');

// Check if an ID is passed
if (isset($_GET['id'])) {
    $id_kasir = $_GET['id'];

    // Prepare SQL query to delete the user
    $sql = "DELETE FROM kasir WHERE id_kasir = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_kasir);

    // Execute the query
    if ($stmt->execute()) {
        // Redirect back to the user list after successful deletion
        header("Location: index.php");
        exit;
    } else {
        // If deletion fails, show an error message
        echo "Error deleting record: " . $conn->error;
    }
}

// Close the connection
$conn->close();
?>
