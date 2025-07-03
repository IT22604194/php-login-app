<?php
$plain_password = "testpass";
$hash = '$2y$10$uL2jQspMJ.qZFGy4CzI7X.Rr3nM3hlftc9xJGK6x/9QU/27ZHHv4W';

if (password_verify($plain_password, $hash)) {
    echo "Password verified!";
} else {
    echo "Password NOT verified!";
}
?>
