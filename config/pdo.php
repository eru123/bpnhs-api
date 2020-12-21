<?php

// WARNING:IF YOU INSTALL LINKER FROM LINKER INSTALLER
// DO NOT CHANGE ARRAY KEYS IN THIS FILE OTHERWISE YOU
// KNOW WHAT YOU ARE DOING.

return [
    
    // Set 'use' to TRUE if you want to use PDO
    "use" => TRUE, // Type: Boolean

    // Set 'model' to TRUE if you want to use PDOModel
    // Type: Boolean
    "model" => TRUE, 

    // pre made models
    // 'model values' must be an existing table name
    // Type: Array
    "models" => [
        "users",
        "class",
        "staff"    
    ],

    // PDO username
    // Type: String
    "user" => "admin", 

    // PDO password 
    // Type: String
    "pass" => "admin", 

    // PDO hostname
    // Type: String
    "host" => "localhost", 
    
    // PDO database name
    // Type: String
    "db" => "mydb", 

    // Database schema
    // format [tb => [col,col,...],tb => [col,col,...], ....]
    // Type: Array
    "schema" => [
        "users" => ["id","fname","mname","lname","address","phone","email","user","pass"],
        "tokens" => ["id","token","user_id","ip","expiration_timestamp"],
        "student" => ["id","user_id"],
        "teacher" => ["id","user_id"],
        "staff" => ["id","user_id"],
        "admin" => ["id","user_id"],
        "class" => ["id","class_name","creator_id","SY_start","SY_end"],
        "class_session" => ["id","class_id","user_id"],
        "announcements" => ["id","title","expiration_timestamp","content","date","author_id"],
        "class_forum_posts" => ["id","title","content","date","class_id","user_id"],
        "class_forum_comments" => ["id","comment","date","forum_post_id","user_id"]
    ],

    // Database Schema method
    // Choices (normal|force|dynamic) default - dynamic
    // Executed when schema is not an emtpy array
    // Be careful using dynamic method, it can alter your selected tables
    // that is specified on your schema
    // Type: String
    "schema_method" => "dynamic"
];