<?php

// Products trait to retrieve products
trait Products 
{
    public function getProductList ()
    {
        $products = file_get_contents('products.json');
        return json_decode($products, 1);
    }
    
    public function getProductBySKU ($sku)
    {
        $products = $this->getProductList();
    
        foreach ($products as $product) {
            if ($product['sku'] === $sku) {
                return $product;
            }
        }
    
        return null;
    }
}

// Promotion traits to retrieve promotions
trait Promotions
{
    public function getPromotions ()
    {
        $promotions = file_get_contents('promotions.json');
        return json_decode($promotions, 1);
    }

    public function getActivePromotions ()
    {
        $promotions = $this->getPromotions();
        
        $activePromotions = [];
        foreach ($promotions as $promo) {
            if ($promo['active']) {
                $activePromotions[] = $promo;
            }
        }
    
        return $activePromotions;
    }
}

// Promotion interface to make sure all promotion implementation stay consistent
interface PromotionInterface
{
    // validates whether the promo is applicable based on the cart
    public function validate ($cart);

    // apply the promotion to the cart
    public function apply (&$cart);
}

// Logic for validation and application for Apple TV promotion
class AppleTV implements PromotionInterface
{
    use Products;

    protected $sku = 'atv';
    protected $threshold = 3;

    public function validate ($cart)
    {
        $count = 0;

        foreach ($cart['products'] as $product) {
            if ($product['sku'] === $this->sku) $count++;
        }     

        return $count >= $this->threshold; true: false;
    }

    public function apply (&$cart)
    {
        $count = 0;

        foreach ($cart['products'] as $index => $product) {
            if ($product['sku'] === $this->sku) $count++;

            // I implemented in such a way that for every 3rd Apple TV 
            // a client buys, he/she gets a 100% for the said Apple TV
            if ($count == 3) {
                $cart['products'][$index]['actualPrice'] = 0;
                $count = 0;
            }
        }
    }
}

// Logic for validation and application for Super IPad promotion
class SuperIPad implements PromotionInterface
{
    use Products;

    protected $sku = 'ipd';
    protected $threshold = 4;

    public function validate ($cart)
    {
        $count = 0;

        foreach ($cart['products'] as $product) {
            if ($product['sku'] === $this->sku) $count++;
        }        

        // Just to note that in question it says, MORE THAN, 
        // not more or equal to 4 units
        return $count > $this->threshold; true: false;
    }

    public function apply (&$cart)
    {
        foreach ($cart['products'] as $index => $product) {
            // For every IPAD will get a hard price slash of $499.99
            if ($product['sku'] === $this->sku) 
                $cart['products'][$index]['actualPrice'] = 499.99;
        }

        return $cart;
    }
}

// Logic for validation and application for MacBook Pro promotion
class MacBookPro implements PromotionInterface
{
    use Products;

    protected $sku = 'mbp';
    protected $freeGiftSKU = 'vga';

    public function validate ($cart)
    {
        foreach ($cart['products'] as $product) {
            if ($product['sku'] === $this->sku)
                return true;
        }

        return false;
    }

    public function apply (&$cart)
    {
        $totalFreeVGA = 0;

        foreach ($cart['products'] as $index => $product) {
            // for every MacBook Pro, a free VGA cable unit is added
            if ($product['sku'] === $this->sku) $totalFreeVGA++;

            // if there is already an existing VGA cable in cart,
            // decrement the count of total free VGA to be given,
            // instead, set the price to 0
            if ($product['sku'] === $this->freeGiftSKU) {
                $totalFreeVGA--;
                $cart['products'][$index]['actualPrice'] = 0;
            }
        }

        $gift = $this->getProductBySKU($this->freeGiftSKU);
        $gift['actualPrice'] = 0;

        $freeGifts = [];
        for ($i = 1; $i <= $totalFreeVGA; $i++) {
            $freeGifts[] = $gift;
        }

        $cart['products'] = array_merge($cart['products'], $freeGifts);
        return $cart;
    }
}

class CheckOut {
    use Products;
    use Promotions;

    public $cart = [];

    public function scan ($sku) 
    {
        // if $sku is not empty
        if (!empty($sku)) {
            $product = $this->getProductBySKU($sku);

            // if $product is found by Products trait
            if (!empty($product)) {

                // add product to cart
                $this->cart['products'][] = $product;
            } else {
                echo "CHECKOUT::WARNING sku not valid" . PHP_EOL;
            }
        } else {
            echo "CHECKOUT::WARNING sku can not be empty" . PHP_EOL;
        }
    }

    public function total () 
    {
        // do not proceed if cart is empty
        if (empty($this->cart)) {
            echo "CHECKOUT::WARNING cart is empty" . PHP_EOL;
            return null;
        }

        // get all active promotion here
        $promotions = $this->getActivePromotions();

        // this checks every promotion available
        // and validate whether the cart is eligible
        foreach ($promotions as $promotion) {
            $p = new $promotion['class']();
            if ($p->validate($this->cart)) {
                $p->apply($this->cart);
            }
        }

        // this calculates the total price
        $this->cart['totalPrice'] = 0;
        foreach ($this->cart['products'] as $product) {
            $this->cart['totalPrice'] += array_key_exists('actualPrice', $product)
                ? $product['actualPrice']
                : $product['price'];
        }

        $this->printOutput();

        return $this->cart;
    }

    // this function prints the output
    public function printOutput () {
        echo "SKUs Scanned: ";

        foreach ($this->cart['products'] as $product) {
            echo $product['sku'] . ', ';
        }

        echo PHP_EOL;
        echo "Total expected: $" . $this->cart['totalPrice'];
    }
}

echo "test promo 1".PHP_EOL;
$checkout = new Checkout();
$checkout->scan('atv');
$checkout->scan('atv');
$checkout->scan('atv');
$checkout->total();

echo PHP_EOL.PHP_EOL.PHP_EOL;

echo "test promo 2".PHP_EOL;
$checkout = new Checkout();
$checkout->scan('ipd');
$checkout->scan('ipd');
$checkout->scan('ipd');
$checkout->scan('ipd');
$checkout->scan('ipd');
$checkout->total();

echo PHP_EOL.PHP_EOL.PHP_EOL;

echo "test promo 3".PHP_EOL;
$checkout = new Checkout();
$checkout->scan('mbp');
$checkout->scan('mbp');
$checkout->scan('mbp');
$checkout->total();

echo PHP_EOL.PHP_EOL.PHP_EOL;

echo "test errors".PHP_EOL;
$checkout = new Checkout();
$checkout->scan('');
$checkout->scan('ipd2');
$checkout->total();

echo PHP_EOL.PHP_EOL.PHP_EOL;

echo "test scenario 1".PHP_EOL;
$checkout = new Checkout();
$checkout->scan('atv');
$checkout->scan('atv');
$checkout->scan('atv');
$checkout->scan('vga');
$checkout->total();

echo PHP_EOL.PHP_EOL.PHP_EOL;

echo "test scenario 2".PHP_EOL;
$checkout = new Checkout();
$checkout->scan('atv');
$checkout->scan('ipd');
$checkout->scan('ipd');
$checkout->scan('atv');
$checkout->scan('ipd');
$checkout->scan('ipd');
$checkout->scan('ipd');
$checkout->total();

echo PHP_EOL.PHP_EOL.PHP_EOL;

echo "test scenario 3".PHP_EOL;
$checkout = new Checkout();
$checkout->scan('mbp');
$checkout->scan('ipd');
$checkout->scan('vga');
$checkout->total();

echo PHP_EOL.PHP_EOL.PHP_EOL;