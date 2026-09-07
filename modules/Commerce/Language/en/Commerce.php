<?php

/**
 * Every word the money side of the site says: the basket, the checkout, the
 * hand-off to a gateway, the order afterwards, the invoice, the currency
 * switcher and the corporate funnel.
 *
 * Four things decide how this file is written, and all four are about the
 * unhappy paths rather than the happy one.
 *
 * **A refusal is a sentence, not a code.** Every way a booking can fail has its
 * own message here, and each one says three things: what happened, whether any
 * money moved, and what to do next. "Error: insufficient inventory" is true and
 * useless; "the last two seats on that date went while you were filling this in"
 * is the same fact told to somebody who can still act on it. The seat that has
 * gone, the coupon that has run out and the gateway that would not start are the
 * moments a commercial site is judged on, so they get the longest sentences in
 * the file rather than the shortest.
 *
 * **Nothing here promises that a browser proves a payment.** The wording on the
 * cart, the payment page and the order page is deliberately consistent about
 * this: a seat is confirmed when the payment provider confirms it to us, not
 * when the buyer is redirected back. `EnrolmentService::fulfil()` is the only
 * thing that changes that, and it is reached from a verified webhook or from an
 * administrator. Copy that congratulates somebody on a booking the database has
 * not confirmed is a promise the school may have to withdraw in front of
 * somebody who has just paid.
 *
 * **Nothing here invents a number, an address or a date.** There is no bank
 * account, no response-time guarantee and no claim about the exam board: the
 * bank-details message says the details are not published yet and offers to send
 * them, and the certification note says plainly that Adobe awards the
 * certification through Certiport and no course can guarantee a pass. An honest
 * gap beats a convincing invention, especially on a page somebody is about to
 * transfer money from.
 *
 * **Placeholders are `{0}`, `{1}`, never concatenation.** CodeIgniter passes any
 * message that receives arguments through ICU MessageFormatter, so a translator
 * can reorder them for their own grammar. Two traps come with that. A literal
 * brace in a message that takes arguments breaks the format; and an apostrophe
 * immediately before a brace escapes the placeholder, so it renders as literal
 * text. Neither shows up as an error — the message simply comes out wrong — so
 * every message below with a placeholder in it was written to avoid both. The
 * same reason is why numbers sit inside sentences that read correctly whether
 * the number is one or twenty: there is no plural machinery here, so "1 seats"
 * has to be designed out rather than caught.
 *
 * Keys are grouped by the thing they describe rather than by the page they
 * appear on, because the same sentence turns up on the basket, the checkout and
 * the order. Two conventions keep that safe: a group key is never also a string
 * key — "cart.coupon" as an array and "cart.coupon" as a message resolve to
 * nothing and print the key on the page — and the group is `pay` rather than
 * `payment` because that is what every call site already asks for. `payment`
 * below is a different thing: the vocabulary of payment *states*, for the
 * account area and the admin.
 *
 * This file is not editable in the Translation Manager. That tool is bound to
 * `Site.php` and the group name "Site", so anything an editor should be able to
 * reword without a deployment belongs there instead. What is here is either
 * transactional wording that has to stay accurate about money, or product facts
 * that have to stay parallel with one another.
 */
return [

    // ── The basket ──────────────────────────────────────────────────────────

    'membership' => [
        'title'        => 'Membership',
        'meta'         => 'One pass, the whole self-paced library. Monthly, quarterly, half-yearly or annual, priced in your own currency.',
        'eyebrow'      => 'Learn at your own pace',
        'heading'      => 'One pass, the whole library',
        'intro'        => 'Every self-paced course, for as long as your membership runs. Start anything, finish in your own time, and take the assessments and certificates that come with them.',
        'library'      => '{0} courses, about {1} hours of material',
        'library_bare' => '{0} courses',
        'per_month'    => '{0} a month',
        'save'         => 'Save {0}',
        'choose'       => 'Choose {0}',
        'billed_once'  => 'Paid once, runs {0} months. Nothing renews automatically.',
        'billed_one'   => 'Paid once, runs one month. Nothing renews automatically.',
        'current'      => 'Your membership runs until {0}.',
        'current_add'  => 'Buying another plan adds its term to the end of that date — you lose nothing by renewing early.',
        'includes'     => 'What a membership includes',
        'inc_library'  => 'Every self-paced course in the catalogue, including ones added while your membership runs.',
        'inc_pace'     => 'Start and stop whenever you like. Your progress is kept.',
        'inc_assess'   => 'The assessments, and a certificate for each course you complete.',
        'inc_nothing'  => 'No automatic renewal and no card kept on file. When the term ends, it ends.',
        'excludes'     => 'What it does not include',
        'exc_taught'   => 'Instructor-led classes — live online and in person — are booked and paid for separately. A membership is the self-paced library.',
        'in_cart'      => '{0} membership',
        'in_cart_term' => '{0} months of the self-paced library',
        'in_cart_one'  => 'One month of the self-paced library',
    ],

    'cart' => [
        'title'         => 'Basket',
        'eyebrow'       => 'Your booking',
        'heading'       => 'Your basket',
        'meta'          => 'The courses and dates you have chosen, with prices in your own currency.',
        'intro'         => 'Check the dates before you go on. Every line names the class you are booking, where it runs and what one seat costs.',
        'intro_empty'   => 'Nothing in it yet.',
        'summary'       => 'Summary',
        'keep_browsing' => 'Keep looking at courses',

        // The hold. Stated with a clock because a fifteen-minute hold nobody
        // mentions is a hold that expires while somebody fetches their card.
        'hold_until' => 'We are holding these seats until {0}. After that they go back on sale to everybody else.',
        'line_held'  => 'Seats held until',

        // Line detail.
        'untitled'      => 'This course',
        'online'        => 'Online',
        'bundle_dates'  => 'Dates chosen after you book',
        'seats'         => 'Seats',
        'per_seat'      => '{0} per seat',
        'line_discount' => '{0} off',
        'update'        => 'Update',
        'update_named'  => 'Update the number of seats for {0}',
        'remove'        => 'Remove',
        'remove_named'  => 'Remove {0} from the basket',
        // Offered when a line is short. `{0}` is at least one, so this reads
        // correctly whether one seat is left or nine.
        'take_remaining' => 'Book {0} instead',

        // Totals.
        'subtotal' => 'Subtotal ({0} in the basket)',
        'discount' => 'Discount',
        'tax'      => 'Tax',
        'total'    => 'Total',

        'checkout'      => 'Go to checkout',
        'checkout_note' => 'Your seats are confirmed when the payment provider confirms your payment to us, not when your browser comes back from it. That is usually a matter of seconds, and we hold the seats throughout.',
        'blocked'       => 'One of the lines above cannot be booked as it stands. Change it or take it out, and the checkout button comes back. We would rather stop you here than after you have typed everybody in.',

        // The basket is priced in the currency it was started in, and prices are
        // published per currency rather than converted, so a basket and a page
        // can honestly disagree.
        'currency_note'   => 'This basket is priced in {0}, which is the currency it was started in.',
        'currency_switch' => 'Switch it to {0}',

        'added'   => 'Added to your basket, and your seats are held while you finish.',
        'updated' => 'Basket updated.',
        'removed' => 'Taken out of your basket. The seats it was holding are back on sale.',

        // What a line says about itself when the class behind it has moved on.
        // See Cart::decorate() for which of these is chosen when.
        'warn' => [
            'missing'   => 'This class is no longer in the schedule. Nothing has been charged for it, and it has to come out of the basket before you can book the rest.',
            'cancelled' => 'This date has been cancelled. Take it out and choose another from the course page, or ask us to run one that suits you.',
            'closed'    => 'Booking has closed on this date. Take it out and pick another from the course page.',
            'gone'      => 'This date sold out while it was sitting in your basket. Nothing has been charged. The course page lists the other dates, and most courses run again within the month.',
            'short'     => 'This date now has room for {0} of the {1} seats on this line. Change the number below, or look at the other dates for this course.',
            'lapsed'    => 'The fifteen-minute hold on these seats has run out, but nobody else has taken them. They are held again the moment you go to checkout.',
        ],

        // Why something could not go into the basket, or could not be changed.
        'fail' => [
            'gone'        => 'The last seats on that date went while you were reading the page. Nothing has been charged. The other dates for this course are listed below.',
            'short'       => 'You asked for {1} seats and that date is down to {0}. Book fewer, or take one of the other dates on this page.',
            'closed'      => 'That date is no longer open for booking. The other dates for this course are below.',
            'missing'     => 'That date is no longer in the schedule.',
            'unpriced'    => 'We do not publish a price for that in {0}. Switch currency, or ask us for a quote and we will price it for you.',
            'not_in_cart' => 'That line is not in your basket any more.',
            'bad_request' => 'We could not tell what you were trying to book. Go back to the course page and choose a date.',
            'unknown'     => 'We could not add that to your basket. Nothing has been charged. Try once more, and tell us if it happens again.',
        ],

        'coupon' => [
            'label'          => 'Discount code',
            'placeholder'    => 'Enter a code',
            'apply'          => 'Apply',
            'remove'         => 'Remove',
            'line'           => 'Discount code',
            'applied'        => 'Code applied. It takes {0} off this booking.',
            'cleared'        => 'Discount code removed.',
            // A stored coupon is re-checked on every render, so one that has
            // since lapsed is worth nothing today and the panel has to say so
            // rather than sit next to a total it did not change.
            'no_longer'      => 'This code no longer applies to what is in the basket.',
            'unknown'        => 'We do not recognise that code. Check it against the email or the page you took it from — a trailing space is the usual culprit.',
            'not_yet'        => 'That code is real, but it does not start until later. It will work then.',
            'expired'        => 'That code has expired. If it reached you this week, tell us and we will look into it.',
            'used_up'        => 'That code has been used as many times as it was issued for.',
            'wrong_currency' => 'That code is issued in a different currency from your basket, so it cannot be applied to it. Switching currency re-prices the basket and may let it through.',
            'min_spend'      => 'That code needs a larger booking than this one. The minimum is stated on the offer it came from.',
            'per_user'       => 'You have already used that code as many times as it allows.',
            'throttled'      => 'That is a lot of codes in one minute. Wait a moment and try again.',
        ],

        // The empty basket is a way back into the catalogue, not a shrug: the
        // person reading it was, a moment ago, trying to spend money.
        'empty' => [
            'heading'         => 'Your basket is empty',
            'body'            => 'Nothing in it yet. Every course page carries its own dates, its price and how many places are left, so the catalogue is the place to start.',
            'expired_heading' => 'Your basket has expired',
            'expired_body'    => 'A basket is kept for a fortnight, and this one is older than that, so the seats it was holding went back on sale. Nothing was charged. Most courses run again within the month.',
            'courses'         => 'Browse all courses',
            'schedule'        => 'See upcoming dates',
            'certificates'    => 'Certificate programmes',
            'on_demand'       => 'Self-paced courses',
        ],
    ],

    // ── Checkout ────────────────────────────────────────────────────────────

    'checkout' => [
        'title'   => 'Checkout',
        'eyebrow' => 'Your booking',
        'meta'    => 'Complete your booking: billing details, who is coming, and how you would like to pay.',
        'intro'   => 'Two things to tell us — who we are billing, and who is coming — and then how you would like to pay.',

        'empty'          => 'There is nothing in your basket to check out.',
        'errors_summary' => 'Something needs changing in {0} of the boxes below. Each one is marked, with what it needs.',
        'back_to_cart'   => 'Back to the basket',

        'guest_ok' => 'You can book without an account. Already have one?',
        'sign_in'  => 'Sign in',

        // Who is paying.
        'billing'      => 'Who we are billing',
        'billing_note' => 'Who the invoice is made out to. It does not have to be the person attending.',
        'name'         => 'Full name',
        'email'        => 'Email address',
        'email_note'   => 'The receipt and the invoice come here. Joining instructions go to each attendee separately.',
        'phone'        => 'Phone',
        'company'      => 'Company',
        'address'      => 'Address',
        'city'         => 'City',
        'postcode'     => 'Postcode',
        'country'      => 'Country',
        'tax_id'       => 'Tax registration number',
        'po_number'    => 'Purchase order number',
        'notes'        => 'Anything we should know',
        'notes_hint'   => 'Accessibility requirements, dietary needs for a classroom day, or a reference your finance department needs on the invoice.',

        // Who is coming. A seat belongs to a person, not to an order.
        'attendees'      => 'Who is coming',
        'attendees_note' => 'A seat belongs to a person rather than to an order. The joining link, the class recording and the certificate all go to the address you put here, so give every seat its own. If one of them is you, use the button to fill in your own details.',
        'programme'      => 'Programme, dates chosen afterwards',
        'seats_count'    => 'Seats: {0}',
        'seats_changed'  => 'Seats have changed on one of your dates since you added it.',
        'seats_short'    => 'Seats on this date have gone since you added it. This line is for {1}, and the number still free is {0}. Change it in the basket, or pick another date.',
        'attendee_n'     => 'Person {0}',
        'attendee_name'  => 'Full name',
        'attendee_email' => 'Email address',
        'attendee_is_me' => 'This one is me',
        // Validation labels, so an error names the seat it belongs to rather
        // than repeating "Name" four times.
        'attendee_name_n'         => 'name of person {0}',
        'attendee_email_n'        => 'email address for person {0}',
        'err_attendee_duplicate'  => 'This address is already on another seat. Each seat needs its own, because the joining link and the certificate are sent to it — two seats sharing an address would enrol one person and turn the other away at the door.',

        // How to pay.
        'payment'         => 'How you would like to pay',
        'no_gateway'      => 'We have no online payment method for this currency at the moment. We can still take the booking and invoice you for it.',
        'ask_for_invoice' => 'Ask us to invoice you',

        // What is being agreed to. Not a formality: it is the record that this
        // buyer was shown the transfer and cancellation terms before paying.
        'terms_label'   => 'Terms and conditions',
        'terms_html'    => 'I have read and accept the {0} and the {1}, including the transfer and cancellation policy.',
        'terms_link'    => 'terms and conditions',
        'privacy_link'  => 'privacy notice',

        'place'      => 'Place the booking ({0})',
        'after_note' => 'Nothing is charged until you finish the payment on the next page, and your seats are held while you do it. A booking is confirmed once the payment provider confirms the payment to us.',

        // The summary alongside.
        'summary'            => 'Your booking',
        'coupon'             => 'Discount code',
        'coupon_placeholder' => 'Enter a code',
        'coupon_apply'       => 'Apply',
        'coupon_saving'      => 'Discount code',
        'subtotal'           => 'Subtotal',
        'discount'           => 'Discount',
        'tax'                => 'Tax',
        'total'              => 'Total',
        'currency_note'      => 'Charged in {0}. Prices are published in each currency rather than converted at the rate of the day, so this is the figure that goes to the payment provider.',

        // The locked re-check refused the basket. Each of these lands the buyer
        // back on the basket, where the date can still be changed.
        'seat_gone'    => 'The last seats on that date went while you were filling this in. Nothing has been charged. Change the date on the line below, or take it out and choose another.',
        'seat_short'   => 'One of your dates no longer has enough seats for the number you are booking. Nothing has been charged. Change the number below, or choose another date.',
        'seat_closed'  => 'One of the classes in your basket closed for booking while you were filling this in. Nothing has been charged.',
        'seat_missing' => 'One of the classes in your basket is no longer in the schedule. Nothing has been charged, and it has to come out before you can book the rest.',

        'err_place' => 'We could not place that order. Nothing has been charged and your basket is exactly as you left it. Try once more, and if it happens again tell us and we will place it for you.',
    ],

    // ── Ways of paying ──────────────────────────────────────────────────────
    //
    // A gateway with no keys configured is never offered, so none of these
    // descriptions has to hedge about whether the method actually works. The
    // `desc_` keys are looked up as 'gateway.desc_' . $gateway->key(), so a new
    // gateway needs a matching key here or its radio button carries no
    // explanation at all.

    'gateway' => [
        'bank'    => 'Bank transfer or invoice',
        'stripe'  => 'Card',
        'payhere' => 'PayHere',
        'paypal'  => 'PayPal',

        'desc_bank'    => 'We send you an invoice and hold your seats until the due date. This is the route most companies want, because a finance department pays against a purchase order. It is the slowest way to a confirmed seat, since the place is confirmed when the money arrives.',
        'desc_stripe'  => 'Pay by card on Stripe. The card details are entered on their page rather than ours, so no card number ever reaches this site.',
        'desc_payhere' => 'Sri Lankan cards, internet banking and mobile wallets, handled by PayHere.',
        'desc_paypal'  => 'Pay from a PayPal balance or a card through PayPal. Nothing about the card is shared with us.',

        // Sent to the gateway as the line description when an order item has
        // lost its title. Kept short: PayHere truncates the field at 100
        // characters, and a truncated description on a bank statement is what
        // a buyer disputes.
        'training' => 'Training',

        'unavailable' => 'We could not reach the payment provider just now. Nothing has been charged.',
    ],

    // ── The hand-off to a gateway ───────────────────────────────────────────

    'pay' => [
        'eyebrow' => 'Payment',
        'title'   => 'Pay for your booking',
        'intro'   => 'Sending you to {0} to finish the payment. Your seats stay held while you do.',

        'redirect_body' => 'You are being sent to {0} to pay. If nothing happens in the next few seconds, use the button.',
        'redirect_cta'  => 'Continue to {0}',

        'bank_title' => 'Pay by bank transfer',
        'bank_body'  => 'Your booking is placed and your seats are held. Transfer the amount below quoting the reference, and we confirm your place the day it reaches us.',
        'held_until' => 'Your seats stay held until then.',
        // No account number is invented here. An account number guessed onto a
        // payment page is money sent to a stranger.
        'bank_details_pending' => 'Our bank details are not published on this page yet. Email us with the reference above and we will send them to you directly — we would rather do that than print an account number we have not checked.',

        'failed_title' => 'We could not start that payment',
        'failed_body'  => 'Nothing has been charged. Order {0} still stands and its seats are still held, so you can pay another way below, or come back to this page later.',

        'other_methods'     => 'Other ways to pay',
        'confirmation_note' => 'Your place is confirmed when the payment provider confirms the payment to us directly, not when your browser returns to this site. That usually takes seconds. Either way, you will hear from us by email.',
    ],

    // ── The order afterwards ────────────────────────────────────────────────
    //
    // Two honest states, and they are kept apart on purpose: paid, when a
    // verified webhook has landed; and waiting, when the money may well have
    // left the buyer's account and we do not know it yet.

    'order' => [
        'eyebrow' => 'Your booking',
        'title'   => 'Order {0}',
        'thanks'  => 'Thank you — your booking is confirmed',

        'paid_intro' => 'Everything on this order is booked, and everybody named on it has been written to.',

        'state_paid'              => 'Paid and confirmed',
        'state_awaiting_transfer' => 'Waiting for your bank transfer',
        'state_awaiting_payment'  => 'Waiting for confirmation of your payment',
        'state_refunded'          => 'Refunded',
        'state_cancelled'         => 'Cancelled',
        'state_failed'            => 'Payment did not go through',

        'paid_body'             => 'Payment confirmed. Every seat on this order is booked, and each person named below has been emailed their own joining details.',
        'awaiting_transfer_body' => 'We are waiting for your transfer to reach the bank, and your seats are held until the due date. When the money arrives somebody here records it, the enrolments are created, and everybody named on the order gets their joining details by email.',
        // The sentence this whole page exists for.
        'awaiting_payment_body' => 'Your payment has not been confirmed to us yet. That is normal, and it is deliberate: we wait for the payment provider to tell us directly rather than believing the page that sent you back here, because that page can be replayed or forged. Your seats are held while we wait, and the moment the confirmation lands you will get an email with the joining details. Give it a few minutes and refresh.',
        'closed_body'           => 'This order is closed, so no seats are held against it. If that is not what you expected, write to us quoting the reference below and we will find out what happened.',

        'pay_by'           => 'Pay by',
        'amount_due'       => 'Amount due',
        'pay_now'          => 'Pay now',
        'transfer_details' => 'Transfer details',
        'view_invoice'     => 'View the invoice',
        'view_proforma'    => 'View the proforma invoice',
        'view_order'       => 'View the order',
        'go_to_account'    => 'Go to my courses',
        'account_note'     => 'Your courses, joining links and certificates all live in your account. If you booked as a guest, register with the email address on this order and everything attaches itself to it.',

        'what_you_booked'    => 'What you booked',
        'attendees_notified' => 'Each person above has been emailed their own joining details.',
        'billed_to'          => 'Billed to',

        'summary'   => 'Order summary',
        'reference' => 'Order reference',
        'placed'    => 'Placed',
        'help'      => 'Anything not right with this booking? Email',
    ],

    // ── The invoice ─────────────────────────────────────────────────────────
    //
    // Available before payment as well as after, because a company cannot pay
    // by transfer without a document to pay against. An order with no invoice
    // row yet prints as a proforma: the invoice series is gapless accounting,
    // issued once at fulfilment, and a number minted for an order that may
    // never be paid puts a hole in it.

    'invoice' => [
        'title'    => 'Invoice',
        'proforma' => 'Proforma invoice',
        'number'   => 'Invoice number',
        'issued'   => 'Issued',
        'due'      => 'Due',

        'status_paid' => 'Paid',
        'status_due'  => 'Due',

        'reg_number' => 'Company registration {0}',
        'tax_number' => 'Tax registration {0}',

        'bill_to'     => 'Billed to',
        'description' => 'Description',
        'qty'         => 'Seats',
        'unit_price'  => 'Unit price',
        'amount'      => 'Amount',
        'total'       => 'Total ({0})',

        'paid_note'       => 'Paid in full on {0}. No further payment is due.',
        'how_to_pay'      => 'How to pay',
        'reference_note'  => 'Please quote {0} as the reference on the transfer. It is what lets us match your payment to this booking the day it arrives, rather than a week later.',
        'awaiting_note'   => 'This booking is awaiting payment. The seats on it are held until the due date above.',
        'buyer_notes'     => 'Your notes',

        'back'  => 'Back to the order',
        'print' => 'Print or save as PDF',
    ],

    // ── Payment states ──────────────────────────────────────────────────────
    //
    // The vocabulary of what has happened to the money, for the account area,
    // the admin and anywhere a payment row is listed. Separate from `pay`,
    // which is the wording of the hand-off itself.

    'payment' => [
        'status_pending'             => 'Awaiting payment',
        'status_paid'                => 'Paid',
        'status_failed'              => 'Failed',
        'status_cancelled'           => 'Cancelled',
        'status_refunded'            => 'Refunded',
        'status_partially_refunded'  => 'Partly refunded',
        'status_chargeback'          => 'Charged back',

        'method'    => 'Paid by',
        'reference' => 'Payment reference',
        'received'  => 'Received',
        'amount'    => 'Amount',

        'awaiting_webhook'    => 'Waiting for the payment provider to confirm this to us.',
        'recorded_by_admin'   => 'Recorded by hand from the bank statement.',
        'not_confirmed_note'  => 'A payment counts as received only when the provider confirms it to this site directly, or when somebody here records a transfer against the bank statement.',
    ],

    // ── Currency ────────────────────────────────────────────────────────────
    //
    // A choice is not a conversion. Prices are published per currency and never
    // converted at runtime, so switching re-reads every line at the other
    // currency's own published price and drops anything that has none — which
    // has to be said, because a basket that quietly loses a line between two
    // page loads is a basket the buyer stops trusting.

    'currency' => [
        'label'     => 'Currency',
        'choose'    => 'Choose the currency you want to be quoted in',
        'switch_to' => 'Show prices in {0}',
        'switched'  => 'Prices are now shown in {0}.',
        'unknown'   => 'We do not publish prices in that currency, so nothing has been changed.',
        'dropped'   => 'We publish no price in {0} for these, so they have come out of your basket: {1}. Everything else has been re-priced, and nothing has been charged.',
        'note'      => 'Prices are published in each currency rather than converted at the rate of the day, so the figure you see is the figure you pay.',
    ],

    // ── The corporate funnel ────────────────────────────────────────────────
    //
    // A conversation and an invoice, never a checkout. The wording throughout
    // is written for a manager with a capability gap and a budget cycle: it
    // answers what a private cohort would look like before it answers what is
    // in one, and it makes no claim the school cannot stand behind — no client
    // names, no case studies, no promised turnaround and nothing about the
    // Adobe exam that Adobe would not recognise.

    'corporate' => [
        'eyebrow' => 'For organisations',
        'title'   => 'Training for teams',
        'heading' => 'Train your team on the software they are expected to use',
        'meta'    => 'Private Adobe Creative Cloud and AI training for teams in Sri Lanka and beyond — at your premises, live online, or both. Tailored to your own files, quoted against a purchase order.',
        'intro'   => 'Any course in the catalogue, run privately for your people, on your dates, rebuilt around the work they actually do. One quote, one invoice, one purchase order.',

        'cta'              => 'Request a quote',
        'browse_catalogue' => 'Browse the course catalogue',

        // The prose band, used when no editor has written the CMS page yet.
        'what' => [
            'title'  => 'What a private cohort actually is',
            'body'   => 'The same course, taught by the same practitioners, with three differences: your team is the only group in the room, the dates are the ones that suit your schedule, and the exercises are rebuilt around your own files. Instead of retouching a stock photograph, your designers retouch your product shots. Instead of a generic brief, your marketers write prompts against your own tone of voice.',
            'body_2' => 'That matters more than it sounds. People take back to their desks what they practised, and practice on somebody else’s templates transfers badly. It also means the awkward questions get asked, because nobody is asking them in front of strangers.',
        ],

        'delivery' => [
            'title' => 'Three ways to run it',
            'intro' => 'The choice is usually decided by where your people sit rather than by what the course is. All three cover the same syllabus.',

            'onsite_title'  => 'At your premises',
            'onsite_text'   => 'We bring the trainer to you. You need a room, a screen and a machine per person with the software installed. Best when the team is in one place and you want the day to feel like a day away from the desk.',

            'virtual_title' => 'Live online',
            'virtual_text'  => 'The same class, taught live in a virtual room, with the trainer looking at each person’s screen when they get stuck. Best for teams spread across offices or time zones, and it can be split over mornings rather than taking whole days.',

            'hybrid_title'  => 'A mixture',
            'hybrid_text'   => 'Some people in a room, some joining online. It needs a little more equipment at your end and a slightly slower pace, and it is often the only honest answer for a team that is half remote.',

            'note' => 'Class size is agreed with the quote. Small enough that everybody gets looked at is the point of running it privately, and we would rather tell you a group is too big than take the booking and disappoint the back row.',
        ],

        'process' => [
            'title' => 'How a quote happens',
            'intro' => 'Four steps, and nothing is charged at any of them until you have a written quote you are happy with.',

            'step1_title' => 'You tell us what you need',
            'step1_text'  => 'The form below: how many people, which courses, roughly when, and where. Five minutes, and the more you can say about what the team does day to day the better the answer.',

            'step2_title' => 'We come back with a proposal',
            'step2_text'  => 'A syllabus shaped to what you described, the dates we can hold, the trainer, and a price per person and in total. If we think a different course fits better, we say so.',

            'step3_title' => 'You approve it',
            'step3_text'  => 'We hold the dates while you get it signed off. When the purchase order arrives we invoice against it, on your payment terms rather than a card form.',

            'step4_title' => 'We run it',
            'step4_text'  => 'The class runs, you get the attendance record and a certificate for each person, and the recording and materials stay available to them afterwards.',

            'note' => 'Nothing above commits you to anything. If the price does not work or the timing slips, tell us and we will either rework it or say honestly that we cannot.',
        ],

        'includes' => [
            'title' => 'What every private cohort carries',
            'intro' => 'Whichever course you run and however it is delivered, all of this is in the price rather than added to it.',

            'tailored'     => 'Exercises rebuilt around your own files, templates and brand',
            'materials'    => 'Course materials and exercise files for every participant, to keep',
            'instructor'   => 'A practising trainer who does this work for a living, not only teaches it',
            'recording'    => 'The class recording, available to your team for twelve months',
            'attendance'   => 'An attendance record for each session, for your training log',
            'certificates' => 'A certificate of completion for every participant',
            'invoice'      => 'A single invoice against your purchase order, on your payment terms',
            'followup'     => 'A follow-up route for questions once people are back at their desks',

            'certification_note' => 'Where a course prepares people for an Adobe Certified Professional exam, the exam itself is set and awarded by Adobe through Certiport. We prepare your team for it; we do not award it, and no training can guarantee a pass.',
        ],

        'final' => [
            'title' => 'Tell us what you need',
            'text'  => 'Five minutes on the form and we will come back with dates, a shaped syllabus and a price you can take to a budget holder. No card, no obligation, and a real person reads it.',
            'email' => 'Or email us at {0}',
            'reply' => 'We read every one of these ourselves. If your dates are tight, say so in the message and we will deal with it first.',
        ],

        'form' => [
            'title' => 'Request a quote',
            'meta'  => 'Tell us how many people, which courses and roughly when, and we will come back with dates, a syllabus and a price.',
            'intro' => 'The more you can tell us, the closer the first proposal will be. Nothing here commits you to anything, and only the starred fields are needed.',

            'required_note'  => 'Fields marked * are needed. Everything else helps, but the enquiry is worth sending without them.',
            'errors_summary' => 'Something needs changing in {0} of the boxes below. Each one is marked, with what it needs.',

            // Section 1: who is asking.
            'about_you' => 'Who is asking',
            'company'   => 'Organisation',
            'name'      => 'Your name',
            'email'     => 'Work email address',
            'phone'     => 'Phone',
            'country'   => 'Country',
            'choose'    => 'Choose one',

            // Section 2: what they need.
            'what_you_need'      => 'What you need',
            'team_size'          => 'How many people',
            'team_size_hint'     => 'A range is fine. Knowing roughly how many changes both the price and how the day is run.',
            'team_1_4'           => '1 to 4 people',
            'team_5_9'           => '5 to 9 people',
            'team_10_19'         => '10 to 19 people',
            'team_20_49'         => '20 to 49 people',
            'team_50_plus'       => '50 people or more',
            'team_not_sure'      => 'Not decided yet',

            'dates'              => 'When you would like it',
            'dates_placeholder'  => 'For example: the second half of March, weekday mornings',
            'dates_hint'         => 'Weeks, months or "before the end of the quarter" are all useful. We will tell you what we can hold.',

            'courses'            => 'Courses you are interested in',
            'courses_hint'       => 'Tick as many as apply. If none of them is quite right, say so below and we will suggest something.',
            'courses_none'       => 'The catalogue is not published yet. Describe what your team needs to learn in the message box below and we will come back with a suggestion.',
            'courses_other'      => 'Something else',
            'courses_other_hint' => 'A subject rather than a course title is fine. Half of what we run privately started as a sentence in this box.',

            // Section 3: how and where.
            'how_and_where'     => 'How and where',
            'mode'              => 'How you would like it delivered',
            'mode_onsite'       => 'At our premises',
            'mode_onsite_hint'  => 'We bring the trainer to you. You provide the room and a machine per person.',
            'mode_virtual'      => 'Live online',
            'mode_virtual_hint' => 'Taught live in a virtual room. Works well for teams across several offices.',
            'mode_hybrid'       => 'A mixture',
            'mode_hybrid_hint'  => 'Some people in a room, some joining online.',
            'mode_unsure'       => 'Not sure yet',
            'mode_unsure_hint'  => 'Tell us where your people are and we will suggest which of the three fits.',

            'location'      => 'Where',
            'location_hint' => 'City is enough at this stage. It tells us about travel and about which trainers are near you.',

            'budget'          => 'Budget, if you have one',
            'budget_hint'     => 'It is not a commitment, and a rough band is enough. Knowing it means we quote something you can actually approve rather than twice.',
            'budget_not_sure' => 'Not decided yet',
            'budget_upto'     => 'Up to {0}',
            'budget_between'  => '{0} to {1}',
            'budget_over'     => 'Over {0}',

            'message'      => 'Anything else we should know',
            'message_hint' => 'What the team does day to day, what they are struggling with, what the training is meant to fix. This is the box that shapes the proposal.',

            // Consent and sending.
            'marketing_opt_in' => 'Email me occasionally about new courses and dates. Unsubscribe in one click, and nothing to do with this enquiry depends on it.',
            'privacy_note'     => 'We use what you send here to answer your enquiry and nothing else. It is not sold, and it is not shared outside the school.',
            'submit'           => 'Send the request',

            'throttled' => 'That is several requests in a short time from here. Wait a few minutes and send it again, or email us directly — nothing has been lost.',
            'failed'    => 'We could not send that just now. Nothing has been lost from the form. Try once more, and if it fails again email us and we will pick it up from there.',

            // The panel alongside the form.
            'next_title'        => 'What happens next',
            'next_body'         => 'Your enquiry goes to a person who can actually schedule a course, not to a queue. They come back by email with dates we can hold, a syllabus shaped to what you described, and a price per person and in total.',
            'next_note'         => 'No price is charged, no card is asked for and nothing is committed until you have a written quote you are happy with.',
            'next_email'        => 'Would rather just write to us? Email',
            'back_to_overview'  => 'Back to training for teams',

            // Replacements for the framework's `in_list` message, which prints
            // the whole permitted list — forty country codes across the page.
            // The only way to fail one of these is a forged or stale form, so
            // the message is short, names the control, and gives away nothing.
            'err_country'   => 'Please choose a country from the list.',
            'err_team_size' => 'Please choose one of the team sizes.',
            'err_mode'      => 'Please choose how you would like the training delivered.',
            'err_budget'    => 'Please choose one of the budget bands, or leave it as not decided yet.',
        ],

        'thanks' => [
            'heading'         => 'Thank you — your request is with us',
            'intro'           => 'A person reads every one of these. Here is the reference to quote if you need to chase it.',
            'reference_label' => 'Your reference',
            'body'            => 'We have your request and it has gone to somebody who can schedule the course rather than into a queue. They will come back to you by email with the dates we can hold, a syllabus shaped to what you described, and a price per person and in total.',
            'next'            => 'Nothing is committed and nothing is charged. If anything about your request has changed since you sent it, reply to the acknowledgement and it goes to the same person.',
            'urgent'          => 'If your dates are tight, say so directly at',
            'see_dates'       => 'See the public schedule',
        ],
    ],

    // ── Cross-cutting failures ──────────────────────────────────────────────
    //
    // The short forms, for anywhere outside the basket and the checkout — a
    // direct booking from the schedule page, the account area, an API reply.
    // The basket and the checkout keep their own longer versions above, because
    // there the buyer is standing on the page that can fix the problem and the
    // message can say which control to use.

    'errors' => [
        'generic'    => 'Something went wrong at our end. Nothing has been charged.',
        'try_again'  => 'Try that once more. If it fails again, tell us and we will do it for you.',
        'not_found'  => 'We could not find that.',
        'forbidden'  => 'That is not something this account can do.',
        'throttled'  => 'That is a lot of attempts in a short time. Wait a minute and try again.',
        'csrf'       => 'That form had been open long enough to go stale, so we did not submit it. Nothing has changed. Load the page again and it will go through.',
        'db'         => 'We could not save that. Nothing has been charged, and nothing has been half-written — the whole thing was rolled back.',

        'cart_expired'   => 'Your basket has expired. A basket is kept for a fortnight, and the seats in this one have gone back on sale. Nothing was charged.',
        'seat_gone'      => 'The last seats on that date have gone. Nothing has been charged.',
        'seat_short'     => 'There are no longer enough seats on that date for the number you asked for. Nothing has been charged.',
        'session_closed' => 'That class is closed for booking.',
        'session_missing' => 'That class is no longer in the schedule.',

        'order_not_found' => 'We could not find that order. Check the reference — it is on your confirmation email — and mind that an order can only be opened from the browser that placed it, or by signing in to the account it belongs to.',
        'already_paid'    => 'That order has already been paid. Nothing further is due on it.',
        'nothing_due'     => 'There is nothing to pay on that order.',
        'payment_pending' => 'That payment has not been confirmed to us yet. Your seats are held while we wait, and you will get an email the moment it lands.',
        'payment_failed'  => 'That payment did not go through, and nothing has been charged. The order and its seats are still there, so you can try again or pay another way.',
        'awaiting_transfer' => 'We are waiting for your bank transfer. Your seats are held until the due date on the invoice.',
        'amount_mismatch' => 'The amount received does not match the amount on the order, so it has not been applied automatically. Somebody here will look at it and be in touch.',

        'no_currency' => 'We do not publish prices in that currency.',
        'no_gateway'  => 'We have no online payment method for that currency at the moment. We can still take the booking and invoice you.',
        'unavailable' => 'We could not reach the payment provider just now. Nothing has been charged, and your basket is untouched.',
    ],
];
