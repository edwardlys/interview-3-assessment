<?php

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

interface PromotionInterface
{
    public function validate ($cart);
    public function apply (&$cart);
}

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

            if ($count == 3) {
                $cart['products'][$index]['actualPrice'] = 0;
                $count = 0;
            }
        }
    }
}

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

        return $count > $this->threshold; true: false;
    }

    public function apply (&$cart)
    {
        foreach ($cart['products'] as $index => $product) {
            if ($product['sku'] === $this->sku) 
                $cart['products'][$index]['actualPrice'] = 499.99;
        }

        return $cart;
    }
}

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
        $totalFreeVGA = 0;

        foreach ($cart['products'] as $index => $product) {
            if ($product['sku'] === $this->sku) $totalFreeVGA++;
            if ($product['sku'] === $this->freeGiftSKU) $totalFreeVGA--;
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
        if (!empty($sku)) {
            $product = $this->getProductBySKU($sku);
            if (!empty($product)) {
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
        if (empty($this->cart)) {
            echo "CHECKOUT::WARNING cart is empty" . PHP_EOL;
            return null;
        }

        $promotions = $this->getActivePromotions();

        foreach ($promotions as $promotion) {
            $p = new $promotion['class']();
            if ($p->validate($this->cart)) {
                $p->apply($this->cart);
            }
        }

        $this->cart['totalPrice'] = 0;
        foreach ($this->cart['products'] as $product) {
            $this->cart['totalPrice'] += array_key_exists('actualPrice', $product)
                ? $product['actualPrice']
                : $product['price'];
        }

        $this->printOutput();

        return $this->cart;
    }

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