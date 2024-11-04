<?php
// Example user data with associated domains and hashed passwords
$users = [
    'thunderpk.com' => [
        'password' => password_hash('Pakistan@123', PASSWORD_DEFAULT), // Hashed password
        'domain' => 'thunderpk.com',
    ],
    'admin@dfab.com' => [
        'password' => password_hash('123', PASSWORD_DEFAULT),
        'domain' => 'dfab.com',
    ],
    'user2@domain2.com' => [
        'password' => password_hash('securepassword2', PASSWORD_DEFAULT),
        'domain' => 'domain2.com',
    ],
];
?>
