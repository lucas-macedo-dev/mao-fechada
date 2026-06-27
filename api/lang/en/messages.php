<?php

return [
    'validation_failed' => 'Validation failed.',
    'unauthenticated' => 'Unauthenticated.',
    'forbidden' => 'Forbidden.',
    'resource_not_found' => 'Resource not found.',
    'http_error' => 'HTTP error.',
    'server_error' => 'Server error.',
    'logged_out' => 'Logged out.',
    'ownership_denied' => 'You are not allowed to access this resource.',
    'category_parent_self' => 'A category cannot be parent of itself.',
    'category_parent_must_be_root' => 'Subcategories cannot be used as parent categories.',
    'category_type_must_match_parent' => 'Subcategory type must match parent category type.',
    'category_type_must_match_children' => 'Category type must match existing subcategory types.',
    'transaction_type_must_match_category' => 'Transaction type must match category type.',
    'category_fallback_name' => 'No Category',
    'category_delete_with_transactions' => 'This category has linked transactions. Deleting it will move them to "No Category". Continue?',

    // Emails
    'email_verify_subject'  => 'Verify your email address',
    'email_verify_heading'  => 'Confirm your email',
    'email_verify_greeting' => 'Hi, :name!',
    'email_verify_body'     => 'Thanks for signing up. Click the button below to verify your email address. This link expires in 60 minutes.',
    'email_verify_action'   => 'Verify email',
    'email_verify_footer'   => 'If you did not create an account, you can safely ignore this email.',
    'email_verify_fallback' => 'If the button above does not work, copy and paste the link below into your browser:',

    'email_reset_subject'   => 'Reset your password',
    'email_reset_heading'   => 'Reset your password',
    'email_reset_greeting'  => 'Hi, :name!',
    'email_reset_body'      => 'We received a request to reset the password for your account.',
    'email_reset_action'    => 'Reset password',
    'email_reset_expiry'    => 'This link expires in :minutes minutes.',
    'email_reset_footer'    => 'If you did not request a password reset, no further action is required.',
    'email_reset_fallback'  => 'If the button above does not work, copy and paste the link below into your browser:',
];
