<?php

namespace App\Http\Controllers\Client;

use App\Currency;
use App\Helper\Reply;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Invoice;
use App\InvoiceItems;
use App\InvoiceSetting;
use App\Product;
use App\Project;
use App\Tax;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Cookie;


class ClientProductController extends ClientBaseController
{
    /**
     * MemberProductController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.products';
        $this->pageIcon = 'icon-basket';
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $productDetails =  [];
        if ($request->hasCookie('productDetails')) {
            $productDetails = json_decode($request->cookie('productDetails'), true);
        }
        $this->productDetails = $productDetails;
        return response(view('client.products.index', $this->data))->cookie('productDetails', json_encode($productDetails));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->lastInvoice = Invoice::orderBy('id', 'desc')->first();
        $this->invoiceSetting = invoice_setting();
        $this->currencies = Currency::all();
        $this->taxes = Tax::all();
        $this->products = Product::where('allow_purchase', 1)->get();
        $productData = [];
        if ($request->hasCookie('productDetails')) {
            $productDetails = json_decode($request->cookie('productDetails'), true);

            $this->quantityArray = array_count_values($productDetails);
            $this->prodc = $productDetails;
            $this->productKeys = array_unique($this->prodc);
            $this->products = Product::where('allow_purchase', 1)->whereIn('id', $this->productKeys)->get();
        }

        return view('client.products.convert_product', $this->data);
    }

    /**
     * @param StoreProductRequest $request
     * @return array
     */
    public function store(Request $request)
    {
        $items = $request->input('item_name');
        $itemsSummary = $request->input('item_summary');
        $cost_per_item = $request->input('cost_per_item');
        $quantity = $request->input('quantity');
        $amount = $request->input('amount');
        $type = $request->input('type');
        $tax = $request->input('taxes');

        if (!$request->has('item_name')) {
            return Reply::error(__('messages.selectProduct'));
        }

        foreach ($quantity as $qty) {
            if (!is_numeric($qty) && (intval($qty) < 1)) {
                return Reply::error(__('messages.quantityNumber'));
            }
        }

        foreach ($cost_per_item as $rate) {
            if (!is_numeric($rate)) {
                return Reply::error(__('messages.unitPriceNumber'));
            }
        }

        foreach ($amount as $amt) {
            if (!is_numeric($amt)) {
                return Reply::error(__('messages.amountNumber'));
            }
        }

        foreach ($items as $itm) {
            if (is_null($itm)) {
                return Reply::error(__('messages.itemBlank'));
            }
        }

        $invoice = new Invoice();
        $invoice->client_id = $this->user->id;
        $invoice->issue_date = Carbon::now()->format('Y-m-d');
        $invoice->due_date = null;
        $invoice->sub_total = round($request->sub_total, 2);
        $invoice->discount = round($request->discount_value, 2);
        $invoice->discount_type = 'percent';
        $invoice->total = round($request->total, 2);
        $invoice->currency_id = $this->global->currency_id;
        $invoice->due_date = Carbon::now()->addDay()->format('Y-m-d');
        $invoice->note = $request->note;
        $invoice->save();

        //log search
        // $this->logSearchEntry($invoice->id, 'Invoice '.$invoice->invoice_number, 'invoices.show', 'invoice');

        return response(Reply::redirect(route('client.invoices.show', $invoice->id), __('messages.invoiceCreated')))->withCookie(Cookie::forget('productDetails'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }


    /**
     * @param $id
     */
    public function update($id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * @return mixed
     */
    public function data()
    {
        $products = Product::select('id', 'name', 'price', 'taxes')
            ->where('allow_purchase', 1)
            ->get();

        return DataTables::of($products)
            ->addColumn('action', function ($row) {
                $button = '<a href="javascript:;" data-product-id="' . $row->id . '" class="btn btn-info add-product">' . __('app.add') . ' <i class="fa fa-plus"></i></a>';

                return $button;
            })
            ->editColumn('name', function ($row) {
                return ucfirst($row->name);
            })
            ->editColumn('price', function ($row) {
                if (!is_null($row->taxes)) {
                    $totalTax = 0;
                    foreach (json_decode($row->taxes) as $tax) {
                        $this->tax = Product::taxbyid($tax)->first();
                        $totalTax = $totalTax + ($row->price * ($this->tax->rate_percent / 100));
                    }
                    return $this->global->currency->currency_symbol . ($row->price + $totalTax);
                } else {
                    return $this->global->currency->currency_symbol . $row->price;
                }
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function addItems(Request $request)
    {
        $this->items = Product::with('tax')->findOrFail($request->id);
        $exchangeRate = Currency::find($this->global->currency_id);

        if (!is_null($exchangeRate) && !is_null($exchangeRate->exchange_rate)) {
            if ($this->items->total_amount != "") {
                $this->items->price = floor($this->items->total_amount * $exchangeRate->exchange_rate);
            } else {
                $this->items->price = floor($this->items->price * $exchangeRate->exchange_rate);
            }
        } else {
            if ($this->items->total_amount != "") {
                $this->items->price = floor($this->items->total_amount);
            } else {
                $this->items->price = floor($this->items->price);
            }
        }
        $this->taxes = Tax::all();
        $view = view('client.products.add-item', $this->data)->render();
        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    public function addCartItem(Request $request)
    {
        $newItem = $request->productID;
        $productDetails = [];

        if ($request->hasCookie('productDetails')) {
            $productDetails = json_decode($request->cookie('productDetails'), true);
        }

        if ($productDetails) {
            if (is_array($productDetails)) {
                $productDetails[] = $newItem;
            } else {
                array_push($productDetails, $newItem);
            }
        } else {
            $productDetails[] = $newItem;
        }

        return response(Reply::successWithData(__('messages.productAdded'), ['status' => 'success', 'productItems' => $productDetails]))->cookie('productDetails', json_encode($productDetails));
    }
    public function removeCartItem(Request $request, $id)
    {
        $productDetails = [];

        if ($request->hasCookie('productDetails')) {
            $productDetails = json_decode($request->cookie('productDetails'), true);
            foreach (array_keys($productDetails, $id) as $key) {
                unset($productDetails[$key]);
            }
        }

        return response(Reply::successWithData(__('messages.deleteSuccess'), ['status' => 'success', 'productItems' => $productDetails]))->cookie('productDetails', json_encode($productDetails));
    }
}
