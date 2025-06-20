<?php
class Database
{
    private $host = '127.0.0.1';
    private $dbname = 'pucc5552_article_db';
    private $username = 'pucc5552_rafidh';
    private $password = 'lJy3j=$R%[=v';
    private $pdo;

    public function __construct()
    {
        try {
            $this->pdo = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
             echo "Connection failed: " . $e->getMessage(); // show real error
            exit;
        }
    }

    public function getConnection()
    {
        return $this->pdo;
    }
}

// Start session
session_start();

// Helper function to redirect
function redirect($url)
{
    header("Location: $url");
    exit();
}

// Helper function to generate slug
function generateSlug($string)
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return trim($slug, '-');
}

// Helper function to truncate text
function truncateText($text, $limit = 150)
{
    if (strlen($text) > $limit) {
        return substr($text, 0, $limit) . '...';
    }
    return $text;
}

// Helper function to format date
function formatDate($date)
{
    return date('F j, Y', strtotime($date));
}

// Helper function to get safe image URL
function getSafeImageUrl($imageUrl)
{
    if (empty($imageUrl)) {
        return null;
    }
    return $imageUrl;
}

// Helper function to get image alt text
function getImageAlt($title, $imageUrl)
{
    if (empty($imageUrl)) {
        return '';
    }

    return htmlspecialchars($title) . ' - Article Image';
}
