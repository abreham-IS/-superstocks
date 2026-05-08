<?php
$hash = '$2y$12$UAEB4PzrBQh46mkUxw3ebeqwdMmiXnoBsnJOdYZdveEK.axNK3kqC';
echo password_verify('admin123', $hash) ? 'true' : 'false';
?>