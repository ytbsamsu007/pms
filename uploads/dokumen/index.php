<?php
// Prevent Directory Listing - Redirect to application root or 403
http_response_code(403);
exit('Access Denied');
