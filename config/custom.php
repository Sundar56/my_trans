<?php

return [

  /*
    |--------------------------------------------------------------------------
    | Contractor Business Type
    |--------------------------------------------------------------------------
    */
  'businesstype' => [0 => 'Sole Trader', 1 => 'Ltd Company'],

  /*
    |--------------------------------------------------------------------------
    | Project status
    |--------------------------------------------------------------------------
    */
  'projectstatus' => [0 => 'Invitation Sent', 1 => 'Accepted', 2 => 'Ongoing', 3 => 'Funds Deposited', 4 => 'Completed', 5 => 'Dispute', 6 => 'Void', 7 => 'All', 8 => 'Decline', 9 => 'Reinvite', 10 => 'Verified', 11 => 'Verify', 12 => 'Partial Payment', 13 => 'Full Payment', 14 => 'Funds Released', 15 => 'Transaction live', 16 => 'Draft', 17 => 'Contractor Acceptance'],
  /*
    |--------------------------------------------------------------------------
    | Task status
    |--------------------------------------------------------------------------
    */
  'taskstatus' => [0 => 'Pending', 1 => 'Ongoing', 2 => 'Completed', 3 => 'Awaiting', 4 => 'Verify'],
  /*
    |--------------------------------------------------------------------------
    | Project Invite status
    |--------------------------------------------------------------------------
    */
  'invitestatus' => [0 => 'Invitesent', 1 => 'Accept', 2 => 'Decline'],
  /*
    |--------------------------------------------------------------------------
    | Transpact Create Type
    |--------------------------------------------------------------------------
    */
  'createtype' => [1 => 'Money Sender', 2 => 'Money Recipient', 3 => 'introducer of 2 parties'],
  /*
    |--------------------------------------------------------------------------
    | Transpact Currency
    |--------------------------------------------------------------------------
    */
  'currency' => [1 => 'GBP', 2 => 'EUR', 3 => 'USD'],
  /*
    |--------------------------------------------------------------------------
    | Transpact Currency
    |--------------------------------------------------------------------------
    */
  'currencysymbol' => [1 => '£', 2 => '€', 3 => '$'],
  /*
    |--------------------------------------------------------------------------
    | Transpact Nature of Transaction
    |--------------------------------------------------------------------------
    */
  'natureoftransaction' => [
    1  => 'Car Sale',
    2  => 'Building Work',
    3  => 'Event / Catering',
    4  => 'Bet',
    5  => 'Other',
    6  => 'Deposit',
    7  => 'Internet Domain Transfer',
    8  => 'IT Programming',
    9  => 'Medical Equipment',
    10 => 'Donation',
    11 => 'Art Work',
    12 => 'Other Goods',
    13 => 'Other Services',
    14 => 'Ticket Sale',
    15 => 'Logistics',
    16 => 'Property Project',
    17 => 'IT Equipment',
    18 => 'Legal Services',
  ],
  /*
    |--------------------------------------------------------------------------
    | Transpact Callback Status
    |--------------------------------------------------------------------------
    */
  'callbackstatus' => [
    6  => 'Transaction Live when no payment made',
    7  => 'Void Transaction',
    10 => 'Paid Other Party in Full',
    11 => 'Arbitration Process Started (Payments Required)',
    12 => 'Paid Other Party in Part',
    16 => 'Payment Received',
    40 => 'Added Payment Received',
    46 => 'Arbitration Payment Received'
  ],
  'customer' => [
    0 => 'Waiting for your Approval',
    1 => 'Awaiting Funds from Transpact',
    2 => 'Contactor to Begin Work',
    4 => 'Task Completed/ Project On-Going',
    5 => 'Project Completed',
  ],
  'contractor' => [
    0 => 'Waiting for Customer to Sign & Accept Project',
    1 => 'Awaiting Customer Deposit',
    2 => 'Funds Deposited - Ready To Start Work',
    4 => 'Work Completed & Agreed By Client',
    5 => 'Project Completed',
  ],
  'customertext' => [
    0 => 'Waiting for your Approval',
    1 => 'Transpact have sent you an email to complete your registration and deposit into the escrow facility',
    2 => 'Awaiting for contractor to complete the task listed below, once they have been completed. We will ask you the customer to verify completion before the contractor can request any funds from the Escrow facility,',
    3 => 'Task 1 Contractor has marked task 1 as completed, please verify and provide acceptance by ticking the customer acceptance button for each relevant task.',
    4 => 'Thanks for accepting task one as completed, the contractor will now request full or partial payment via transpact.',
  ],
  'contractortext' => [
    0 => 'An invite has been sent to the customer, once they have signed and accepted they’ll be able to pay the project funds into escrow.',
    1 => 'The customer has accepted they project, once they pay the funds into escrow you’ll be notified to commence the project work.',
    2 => "The project funds are now securely deposited into the escrow. Please begin completion of the agreed work, once you've completed a task you can tick the ‘Contractor Acceptance’ box within the task details to show a task is complete.",
    3 => "Task 1 You have marked task 1 as complete, we are now waiting for the customer to verify the task has been completed and you will then be able to request either a full or partial payment",
    4 => 'The customer has accepted that the project has been completed, you can now request full or partial payment.',
  ],

  'acceptterms' => [
    0 => 'Yet to accept the terms and agreed to this transaction in transpact. Please accept the terms and agreement in transpact',
    1 => 'Contractor has not yet accepted the terms and agreed to this transaction in transpact, this transaction has not yet come into existence, and you are not yet protected.',
  ],


];
