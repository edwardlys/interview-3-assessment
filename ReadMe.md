How to run the script

1. The script already has some test samples scripted into it. To see the output of each test samples run:
    `php checkout.php`

2. To add more scenarios append to the bottom of the script:
    ```
    echo PHP_EOL.PHP_EOL.PHP_EOL;
    echo "the name of the test scenario";
    $checkout = new Checkout();
    $checkout->scan('the sku that you would want to scan'); //repeat this however much you need
    $checkout->total(); // this will calculate and print the output
    ```
    And then run `php checkout.php`

3. How to add new SKUs? 
    - You can add more SKU by appending it into the products.json file. As I am expecting to use a MYSQL database,
    the properties that are mandatory is "sku":"string", "name":"string" and "price":"float"

4. How to add new Promotions?
    - I hope I built this to be as scalable as possible. To add new promotions, append into promotions.json with
    mandatory properties "name":"string", "class":"string", "active":"boolean"
    - Using the "class" string in promotions.json, create new Class that implements Promotion interface. Add the logics
    for promotion validation and application into the class under method "validate()" and "apply()"