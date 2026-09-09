<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MessageTemplate;
use App\Models\Admin;

class MessageTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin (or super admin) to assign as creator
        $admin = Admin::first();
        $adminId = $admin ? $admin->id : 1;

        $templates = [
            [
                'title' => 'Welcome Message',
                'content' => 'Thank you for contacting us! We appreciate you reaching out. One of our customer support representatives will connect with you shortly to assist you. Please feel free to share any details about your inquiry.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Order Status Inquiry',
                'content' => 'Thank you for your order status inquiry. We are currently checking your order details and will provide you with an update shortly. Your order is important to us and we appreciate your patience.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Delivery Information',
                'content' => 'Your order is currently being prepared for delivery. Our delivery team will contact you 30 minutes before arrival. Please ensure someone is available to receive the order at the provided address.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Payment Issue Resolution',
                'content' => 'We understand you are experiencing a payment-related issue. Our team is reviewing your case and will resolve this matter within 24-48 hours. We will keep you updated on the progress.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Refund Process',
                'content' => 'Your refund request has been received and is being processed. Refunds typically take 3-5 business days to reflect in your original payment method. You will receive a confirmation once the refund is completed.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Technical Support',
                'content' => 'We are sorry to hear you are experiencing technical difficulties. Our technical team is looking into this issue. In the meantime, please try refreshing the app or logging out and back in.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Account Verification',
                'content' => 'To proceed with your account verification, please provide the following documents: 1) Valid ID proof, 2) Address verification. You can upload these documents through your profile settings or share them here.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Store Availability',
                'content' => 'Thank you for your inquiry about store availability. Our partner stores operate from 9:00 AM to 11:00 PM daily. Some stores may have different timings, which you can check in the store details section.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Promotional Offers',
                'content' => 'We have exciting offers and discounts available! Check the "Offers" section in your app for current promotions. Don\'t forget to apply coupon codes at checkout to get the best deals.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Food Quality Concern',
                'content' => 'We sincerely apologize for any quality issues with your order. Food quality is our top priority. Please share the order details and photos if possible, so we can address this with the restaurant and arrange appropriate compensation.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Delivery Delay Apology',
                'content' => 'We apologize for the delay in your delivery. Due to high demand and traffic conditions, your order is taking longer than expected. We are tracking your order closely and will expedite the delivery process.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'App Usage Help',
                'content' => 'Need help navigating our app? Here are some quick tips: 1) Use the search function to find restaurants, 2) Check reviews and ratings, 3) Track your order in real-time, 4) Save favorite restaurants for quick access.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Contact Information',
                'content' => 'For immediate assistance, you can reach us through: 📞 Customer Support: [Your Phone Number], 📧 Email: [Your Email], 🕐 Support Hours: 24/7. We are here to help you with any questions or concerns.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Order Cancellation',
                'content' => 'Your order cancellation request has been received. If the order hasn\'t been prepared yet, the cancellation will be processed immediately. If preparation has started, please contact us for further assistance.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Thank You Follow-up',
                'content' => 'Thank you for choosing our service! We hope you enjoyed your order. Your feedback is valuable to us - please take a moment to rate your experience. We look forward to serving you again soon!',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Escalation Notice',
                'content' => 'Your concern has been escalated to our senior support team for immediate attention. A supervisor will review your case and contact you within 2-4 hours with a resolution. We appreciate your patience.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Weekend/Holiday Notice',
                'content' => 'Please note that during weekends and holidays, response times may be slightly longer than usual. We will address your inquiry as soon as possible during our next business hours. Thank you for your understanding.',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'New User Welcome',
                'content' => 'Welcome to our platform! 🎉 We\'re excited to have you join our community. Here\'s how to get started: 1) Complete your profile, 2) Add your delivery address, 3) Browse restaurants near you, 4) Place your first order and enjoy!',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Feature Update Notice',
                'content' => 'We\'ve added new features to enhance your experience! Update your app to the latest version to access: improved search, better order tracking, new payment options, and exclusive offers. Happy ordering!',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'title' => 'Closing Acknowledgment',
                'content' => 'Thank you for contacting our customer support. Your issue has been resolved. If you need any further assistance, please don\'t hesitate to reach out. Have a wonderful day and thank you for choosing our service!',
                'is_active' => true,
                'created_by' => $adminId,
            ]
        ];

        foreach ($templates as $template) {
            MessageTemplate::create($template);
        }
    }
}
