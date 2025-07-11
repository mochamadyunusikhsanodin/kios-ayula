<?php
// This script creates the necessary directory structure for storing uploaded receipt images

// Define the directory path
$upload_dir = '../uploads/nota/';

// Check if directory exists
if (!file_exists($upload_dir)) {
    // Create directory with full permissions (for development)
    if (mkdir($upload_dir, 0777, true)) {
        echo "Directory created successfully: " . $upload_dir;
        
        // Set proper permissions (more secure for production)
        chmod($upload_dir, 0755);
        echo "<br>Permissions set to 0755";
        
        // Create .htaccess file to protect direct access to images (optional)
        $htaccess_content = "# Deny direct access to files
<FilesMatch \"\\.(jpg|jpeg|png|gif)$\">
  Order Allow,Deny
  Deny from all
</FilesMatch>

# Allow access through PHP scripts
<FilesMatch \"get_image\\.php$\">
  Order Allow,Deny
  Allow from all
</FilesMatch>";
        
        file_put_contents($upload_dir . '/.htaccess', $htaccess_content);
        echo "<br>.htaccess file created for security";
        
    } else {
        echo "Failed to create directory: " . $upload_dir;
        echo "<br>Please create this directory manually and ensure it has write permissions.";
    }
} else {
    echo "Directory already exists: " . $upload_dir;
    
    // Check if directory is writable
    if (is_writable($upload_dir)) {
        echo "<br>Directory is writable.";
    } else {
        echo "<br>Warning: Directory is not writable. Please set proper permissions.";
    }
}

echo "<br><br><a href='/ayula-store/views/barang/productlist.php'>Return to Product List</a>";
?>