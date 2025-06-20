<?php

/**
 * Authentication Middleware
 * Include this file at the top of admin pages to ensure proper authentication
 */

require_once dirname(__FILE__) . '/../auth.php';

// Validate session and require login
if (!$auth->validateSession()) {
    $currentPage = $_SERVER['REQUEST_URI'];
    redirect('../login.php?redirect=' . urlencode($currentPage));
}

// Get current user for use in templates
$currentUser = $auth->getCurrentUser();

// Make auth functions easily available
function getCurrentUser()
{
    global $currentUser;
    return $currentUser;
}

function canEditArticle($articleId)
{
    global $auth;
    return $auth->canEditArticle($articleId);
}

function requireAdminAccess()
{
    global $auth;
    $auth->requireAdmin();
}
