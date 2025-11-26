<?php
/**
 * Safe Image Cleanup Script
 * Removes images that have been migrated to Cloudinary
 */

echo "<h1>🧹 Image Cleanup Script</h1>";

// Files to remove (confirmed migrated to Cloudinary)
$filesToRemove = [
    '../assets/images/vanessainicio.jpg'
];

// Class images folder to remove
$classesFolder = '../assets/images/classes/';

echo "<h2>Files to be removed:</h2>";
echo "<ul>";

// Check individual files
foreach ($filesToRemove as $file) {
    if (file_exists($file)) {
        echo "<li>✅ {$file} (exists)</li>";
    } else {
        echo "<li>❌ {$file} (not found)</li>";
    }
}

// Check classes folder
if (is_dir($classesFolder)) {
    $classImages = glob($classesFolder . '*');
    echo "<li>📁 {$classesFolder} (contains " . count($classImages) . " files)</li>";
    foreach ($classImages as $image) {
        echo "<li style='margin-left: 20px;'>📄 " . basename($image) . "</li>";
    }
} else {
    echo "<li>❌ {$classesFolder} (not found)</li>";
}

echo "</ul>";

// Cleanup confirmation
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    echo "<h2>🚀 Performing cleanup...</h2>";
    
    $removedFiles = 0;
    $errors = [];
    
    // Remove individual files
    foreach ($filesToRemove as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                echo "<p>✅ Removed: {$file}</p>";
                $removedFiles++;
            } else {
                echo "<p>❌ Failed to remove: {$file}</p>";
                $errors[] = $file;
            }
        }
    }
    
    // Remove classes folder and contents
    if (is_dir($classesFolder)) {
        $classImages = glob($classesFolder . '*');
        foreach ($classImages as $image) {
            if (unlink($image)) {
                echo "<p>✅ Removed: {$image}</p>";
                $removedFiles++;
            } else {
                echo "<p>❌ Failed to remove: {$image}</p>";
                $errors[] = $image;
            }
        }
        
        // Remove the folder itself
        if (rmdir($classesFolder)) {
            echo "<p>✅ Removed folder: {$classesFolder}</p>";
        } else {
            echo "<p>❌ Failed to remove folder: {$classesFolder}</p>";
            $errors[] = $classesFolder;
        }
    }
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Cleanup Complete!</h3>";
    echo "<p><strong>Files removed:</strong> {$removedFiles}</p>";
    
    if (empty($errors)) {
        echo "<p>✅ All files successfully removed. Your project is now using Cloudinary exclusively!</p>";
    } else {
        echo "<p>⚠️ Some files couldn't be removed:</p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>{$error}</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
} else {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ Confirmation Required</h3>";
    echo "<p>This will permanently delete the old image files that have been migrated to Cloudinary.</p>";
    echo "<p><strong>Make sure:</strong></p>";
    echo "<ul>";
    echo "<li>✅ All images are working correctly on your website</li>";
    echo "<li>✅ Cloudinary integration is fully functional</li>";
    echo "<li>✅ You have backups if needed</li>";
    echo "</ul>";
    echo "<p><a href='?confirm=yes' class='btn' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🗑️ Yes, Delete Old Images</a></p>";
    echo "</div>";
}

echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📋 What This Script Does:</h3>";
echo "<ul>";
echo "<li><strong>Removes:</strong> Local image files that are now served from Cloudinary</li>";
echo "<li><strong>Keeps:</strong> Other asset folders (gallery, products, social) for future use</li>";
echo "<li><strong>Preserves:</strong> The main assets/images/ folder structure</li>";
echo "<li><strong>Safe:</strong> Only removes files confirmed to be on Cloudinary</li>";
echo "</ul>";
echo "</div>";
?>