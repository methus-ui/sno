<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vendor_employees', function (Blueprint $table) {
            // Contact fields
            $table->string('alternate_phone', 20)->nullable()->after('phone');
            $table->string('father_phone', 20)->nullable()->after('alternate_phone');
            $table->string('family_contact_phone', 20)->nullable()->after('father_phone');
            $table->string('family_contact_name', 100)->nullable()->after('family_contact_phone');

            // Aadhar & Address
            $table->string('aadhar_number', 12)->nullable()->after('family_contact_name');
            $table->string('address_line1')->nullable()->after('aadhar_number');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('city', 100)->nullable()->after('address_line2');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('pincode', 6)->nullable()->after('state');

            // Employment fields
            $table->date('date_of_joining')->nullable()->after('pincode');
            $table->text('past_experience')->nullable()->after('date_of_joining');

            // Verification fields
            $table->boolean('police_verification_status')->default(false)->after('past_experience');
            $table->date('police_verification_date')->nullable()->after('police_verification_status');
            $table->string('police_verification_document')->nullable()->after('police_verification_date');
            $table->boolean('cancelled_cheque_submitted')->default(false)->after('police_verification_document');
            $table->string('cancelled_cheque_document')->nullable()->after('cancelled_cheque_submitted');

            // Contract fields
            $table->integer('probation_period_days')->default(90)->after('cancelled_cheque_document');
            $table->integer('notice_period_days')->default(30)->after('probation_period_days');
            $table->boolean('joining_letter_sent')->default(false)->after('notice_period_days');
            $table->timestamp('joining_letter_sent_at')->nullable()->after('joining_letter_sent');

            // Indexes for verification dashboard queries
            $table->index('police_verification_status');
            $table->index('cancelled_cheque_submitted');
            $table->index('joining_letter_sent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vendor_employees', function (Blueprint $table) {
            $table->dropIndex(['police_verification_status']);
            $table->dropIndex(['cancelled_cheque_submitted']);
            $table->dropIndex(['joining_letter_sent']);

            $table->dropColumn([
                'alternate_phone',
                'father_phone',
                'family_contact_phone',
                'family_contact_name',
                'aadhar_number',
                'address_line1',
                'address_line2',
                'city',
                'state',
                'pincode',
                'date_of_joining',
                'past_experience',
                'police_verification_status',
                'police_verification_date',
                'police_verification_document',
                'cancelled_cheque_submitted',
                'cancelled_cheque_document',
                'probation_period_days',
                'notice_period_days',
                'joining_letter_sent',
                'joining_letter_sent_at'
            ]);
        });
    }
};
