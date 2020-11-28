<?php

namespace App\Http\Controllers\Client;

use App\ClientPayment;
use App\Helper\Reply;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Invoice;
use App\Payment;
use App\PaymentGatewayCredentials;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Stripe\Subscription;
use Validator;
use URL;
use Session;
use Redirect;

use Stripe\Charge;
use Stripe\Customer;
use Stripe\Plan;
use Stripe\Stripe;

class StripeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $stripeCredentials = PaymentGatewayCredentials::first();

        /** setup Stripe credentials **/
        Stripe::setApiKey($stripeCredentials->stripe_secret);
        $this->pageTitle = 'Stripe';
    }

    /**
     * Store a details of payment with paypal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paymentWithStripe(Request $request, $invoiceId)
    {
        $redirectRoute = 'client.invoices.show';
        $id = $invoiceId;

        return $this->makeStripePayment($request, $invoiceId, $redirectRoute, $id);
    }

    public function paymentWithStripePublic(Request $request, $invoiceId)
    {
        $redirectRoute = 'front.invoice';
        $id = md5($invoiceId);

        return $this->makeStripePayment($request, $invoiceId, $redirectRoute, $id);
    }

    private function makeStripePayment($request, $invoiceId, $redirectRoute, $id)
    {
        $invoice = Invoice::findOrFail($invoiceId);

        // dd($invoice->amountDue());

        $tokenObject  = $request->get('token');
        $token  = $tokenObject['id'];
        $email  = $tokenObject['email'];
        $name = $request->name;
        $line1 = $request->line1;
        $postal_code = $request->postal_code;
        $city = $request->city;
        $state = $request->state;
        $country = $request->country;

        if ($invoice->recurring == 'no') 
        {
            try {
                $customer = Customer::create(array(
                    'name' => $name,
                    'email' => $email,
                    'address' => [
                        'line1' => $line1,
                        'postal_code' => $postal_code,
                        'city' => $city,
                        'state' => $state,
                        'country' => $country,
                    ],
                    'source'  => $token,
                ));

                $charge = Charge::create(array(
                    'customer' => $customer->id,
                    'amount'   => $invoice->amountDue() * 100,
                    'currency' => $invoice->currency->currency_code,
                    'description' => $invoice->invoice_number. ' Payment',
                ));
            } catch (\Exception $ex) {
                \Session::put('error', __('messages.errorOccured'));
                return Reply::redirect(route($redirectRoute, $id), 'Payment fail');
            }

            $payment = new ClientPayment();
            $payment->project_id = $invoice->project_id;
            $payment->invoice_id = $invoice->id;
            $payment->currency_id = $invoice->currency_id;
            $payment->amount = $invoice->amountDue();
            $payment->gateway = 'Stripe';
            $payment->transaction_id = $charge->id;
            $payment->paid_on = Carbon::now();
            $payment->status = 'complete';
            $payment->save();
        } else {


            $plan = Plan::create(array(
                "name" => 'Payment for invoice #' . $invoice->invoice_number,
                "id" => 'plan-' . $invoice->id . '-' . str_random('10'),
                "interval" => $invoice->billing_frequency,
                "interval_count" => $invoice->billing_interval,
                "currency" => $invoice->currency->currency_code,
                "amount" => $invoice->total * 100,
                "metadata" => [
                    "invoice_id" => $invoice->id
                ],
            ));

            try {

                $customer = Customer::create(array(
                    'name' => $name,
                    'email' => $email,
                    'address' => [
                        'line1' => $line1,
                        'postal_code' => $postal_code,
                        'city' => $city,
                        'state' => $state,
                        'country' => $country,
                    ],
                    'source'  => $token,
                ));

                $subscription = Subscription::create(array(
                    "customer" => $customer->id,
                    "items" => array(
                        array(
                            "plan" => $plan->id,
                        ),
                    ),
                    "metadata" => [
                        "invoice_id" => $invoice->id
                    ],
                ));
            } catch (\Exception $ex) {
                \Session::put('error', $ex->getMessage());
                return Reply::redirect(route($redirectRoute, $id), 'Payment fail');
            }

            // Save details in database
            $payment = new ClientPayment();
            $payment->project_id = $invoice->project_id;
            $payment->currency_id = $invoice->currency_id;
            $payment->amount = $invoice->total;
            $payment->gateway = 'Stripe';
            $payment->plan_id = $plan->id;
            $payment->transaction_id = $subscription->id;
            $payment->paid_on = Carbon::now();
            $payment->status = 'complete';
            if (!is_null($invoice->project_id)) {
                $payment->project_id = $invoice->project_id;
            }
            $payment->save();
        }

        $invoice->status = 'paid';
        $invoice->save();

        \Session::put('success', 'Payment success');
        return Reply::redirect(route($redirectRoute, $id), 'Payment success');
    }
}
