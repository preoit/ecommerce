<?php

$moduleRoutes = [
    __DIR__.'/Dashboard/Routes/web.php',
    __DIR__.'/Inventories/Products/Routes/web.php',
    __DIR__.'/Inventories/Categories/Routes/web.php',
    __DIR__.'/Inventories/Brands/Routes/web.php',
    __DIR__.'/Inventories/Units/Routes/web.php',
    __DIR__.'/Orders/Routes/web.php',
    __DIR__.'/Customers/Routes/web.php',
    __DIR__.'/Coupons/Routes/web.php',
    __DIR__.'/Reviews/Routes/web.php',
    __DIR__.'/Reports/Routes/web.php',
    __DIR__.'/FileManager/Routes/web.php',
    __DIR__.'/Settings/Routes/web.php',
];

foreach ($moduleRoutes as $moduleRoute) {
    require $moduleRoute;
}
