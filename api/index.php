<?php
require_once 'config.php';

$endpoints = [
    'base_url' => 'https://yourdomain.com/api',
    'authentication' => [
        'login' => [
            'method' => 'POST',
            'endpoint' => '/auth/login.php',
            'description' => 'User login',
            'parameters' => ['login', 'password']
        ],
        'register' => [
            'method' => 'POST',
            'endpoint' => '/auth/register.php',
            'description' => 'User registration',
            'parameters' => ['full_name', 'university_email', 'contact', 'address', 'faculty', 'year_of_study', 'password']
        ]
    ],
    'products' => [
        'list' => [
            'method' => 'GET',
            'endpoint' => '/products/',
            'description' => 'Get products with filtering and pagination',
            'parameters' => ['page', 'limit', 'search', 'category', 'min_price', 'max_price', 'in_stock', 'sort']
        ],
        'create' => [
            'method' => 'POST',
            'endpoint' => '/products/',
            'description' => 'Create new product (admin only)',
            'parameters' => ['name', 'category', 'price', 'stock', 'description', 'image_url']
        ]
    ],
    'orders' => [
        'list' => [
            'method' => 'GET',
            'endpoint' => '/orders/',
            'description' => 'Get orders',
            'parameters' => ['user_id', 'page', 'limit', 'status']
        ],
        'create' => [
            'method' => 'POST',
            'endpoint' => '/orders/',
            'description' => 'Create new order',
            'parameters' => ['account_id', 'items', 'delivery_method', 'payment_method', ...]
        ]
    ],
    'payment' => [
        'initiate' => [
            'method' => 'POST',
            'endpoint' => '/payment/initiate.php',
            'description' => 'Initiate payment',
            'parameters' => ['order_id', 'amount', 'phone_number', 'provider']
        ],
        'verify' => [
            'method' => 'POST',
            'endpoint' => '/payment/verify.php',
            'description' => 'Verify payment status',
            'parameters' => ['transaction_id']
        ]
    ],
    'notifications' => [
        'list' => [
            'method' => 'GET',
            'endpoint' => '/notifications/',
            'description' => 'Get user notifications',
            'parameters' => ['user_id', 'page', 'limit', 'unread_only']
        ]
    ]
];

APIResponse::success($endpoints, 'UniKart API Documentation');
?>