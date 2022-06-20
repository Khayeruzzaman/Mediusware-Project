<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;



class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        
        if($request->price_from OR $request->price_to ){

            // $products = Product::with('product_variant_prices')
            // ->whereBetween('product_variant_prices.price', [$request->price_from, $request->price_to])
            // ->get()
            // ->orderBy('id', 'asc')->paginate(5);
            // $variants = Variant::all();
          
            $price = array($request->price_to, $request->price_from);
            $products = Product::with('product_variant_prices')
            ->whereHas('product_variant_prices', function($q) use($price)
                            {
                                $q->whereBetween('price', $price);
                            })
            ->orderBy('id', 'asc')->paginate(5);
            
            
            $variants = Variant::all();

            return view('products.index')->with('products', $products)
            ->with('variants', $variants);

        }
        if($request->date)
        {
            $products = Product::where('created_at', 'LIKE', '%' . $request->date . '%')
            ->orderBy('id', 'asc')->paginate(5);
            $variants = Variant::all();
        
        
            return view('products.index')->with('products', $products)
            ->with('variants', $variants);
        }
        
        if($request->title)
        {
            $products = Product::where('title', 'LIKE', '%' . $request->title . '%')
            ->orderBy('id', 'asc')->paginate(5);
            $variants = Variant::all();
        
        
            return view('products.index')->with('products', $products)
            ->with('variants', $variants);
        }

        if($request->variant){

            
            $variant = $request->variant;
            $products = Product::with('product_variants')
            ->whereHas('product_variants', function($q) use($variant)
                            {
                                
                                $q->where('variant','LIKE','%'.$variant.'%');
                            })
            ->orderBy('id', 'asc')->paginate(5);
            $variants = Variant::all();
            
            
            return view('products.index')->with('products', $products)
            ->with('variants', $variants);

        }

        $products = Product::orderBy('id', 'asc')->paginate(5);
            $variants = Variant::all();
        
        
            return view('products.index')->with('products', $products)
            ->with('variants', $variants);
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {

        $this->validate($request, [
            'title' => 'required',
        ]);

        $product = Product::orderBy('id', 'desc')->limit(1)->first();
        $id = $product->id;
        $product = new Product;
        $product->id = $id+1;
        $product->title = $request->input('title');
        $product->sku = $request->input('sku');
        $product->description = $request->input('description');

        $product->save();

        

        $variants = [];
        foreach($request->input('product_variant') as $variant){
            $variant_id = $variant['option'];
            foreach($variant['tags'] as $tag){
                $var_item = ['variant' => $tag, 'variant_id' => $variant_id, 'product_id' => $product->id];
                array_push($variants, $var_item);
            }
        }

        $product->product_variants()->createMany($variants);

        $variant_prices = [];

        foreach($request->input('product_variant_prices') as $variant){
            $variant_one = null;
            $variant_two = null;
            $variant_three = null;
            foreach(explode("/",$variant['title']) as $item){
                $product_variant = $product->product_variants()->where('variant', $item)->first();
                if($product_variant){
                    if($product_variant->variant_id == 1){
                        $variant_one = $product_variant->id;
                    }elseif($product_variant->variant_id == 2){
                        $variant_two = $product_variant->id;
                    }else{
                        $variant_three = $product_variant->id;
                    }
                }
            }
            $var_item = array('product_variant_one' => $variant_one, 
                                'product_variant_two' => $variant_two, 
                                'product_variant_three' => $variant_three, 
                                'price' => $variant['price'], 
                                'stock' => $variant['stock']);
            array_push($variant_prices, $var_item);
        }

        $product->product_variant_prices()->createMany($variant_prices);

        $response = "The product has been created successfully!";
        return response($response);


    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        return view('products.edit', compact('variants'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
