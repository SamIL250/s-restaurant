# Restaurant Subscription Management System

This system allows restaurants to manage customer subscriptions for food services, including monthly meal plans, daily dining options, and automated payment tracking.

## Features

### 🍽️ Subscription Types
- **Lunch Only - Monthly**: Daily lunch service for 30 days ($150)
- **Dinner Only - Monthly**: Daily dinner service for 30 days ($180)
- **All Day - Monthly**: Unlimited meals throughout the day for 30 days ($300)
- **Lunch + Beverages - Monthly**: Daily lunch with beverages for 30 days ($200)
- **Premium - Monthly**: All day access with premium menu items and beverages ($450)
- **Weekly Trial**: 7-day trial subscription ($50)

### 📊 Customer Management
- Track subscription status (Active, Expired, Cancelled, Pending)
- Monitor subscription start/end dates
- Payment method tracking (Cash, Card, Mobile Money, Bank Transfer)
- Auto-renewal options
- Loyalty points integration

### 🔔 Alert System
- **Expiring Soon**: Alerts for subscriptions ending within 7 days
- **Payment Due**: Reminders for upcoming payments
- **Expired**: Notifications for expired subscriptions
- **Renewal Reminders**: Automated renewal notifications

### 💰 Payment Tracking
- Record all subscription payments
- Track payment methods and status
- Generate transaction references
- Payment history per customer

## Database Schema

### New Tables Added

#### 1. `subscription_types`
- Defines different subscription packages
- Configurable duration, price, meals per day
- Service hours and beverage inclusion options

#### 2. `subscription_payments`
- Tracks all subscription payments
- Links to customers and subscription types
- Payment status and transaction references

#### 3. `subscription_usage`
- Monitors customer usage of their subscription
- Tracks meals consumed per day
- Links to menu items and quantities

#### 4. `subscription_alerts`
- System-generated alerts and notifications
- Customer-specific messages
- Read/unread status tracking

### Customer Table Updates
Added new columns to the existing `customers` table:
- `has_subscription` - Boolean flag for subscription status
- `subscription_status` - Current subscription state
- `subscription_start_date` - When subscription began
- `subscription_end_date` - When subscription expires
- `subscription_type_id` - Reference to subscription type
- `payment_method` - Preferred payment method
- `auto_renewal` - Auto-renewal preference
- `last_payment_date` - Date of last payment
- `next_payment_date` - When next payment is due

## Installation

### 1. Database Setup
Run the SQL script to create the subscription system:
```sql
-- Execute the subscription_schema.sql file
source config/subscription_schema.sql;
```

### 2. File Structure
Ensure these files are in place:
```
src/
├── services/
│   └── customers/
│       ├── manage_subscription.php
│       ├── get_subscription_alerts.php
│       └── add_subscription_type.php
└── pages/
    └── customers/
        ├── main.php (updated)
        ├── subscriptions.php
        └── subscription_types.php
```

### 3. Navigation Updates
Add subscription management links to your main navigation:
```php
<a href="customers/subscriptions">Subscription Dashboard</a>
<a href="customers/subscription_types">Subscription Types</a>
```

## Usage

### Adding a Customer Subscription

1. **Navigate to Customers page**
2. **Click the green "+" button** next to a customer without a subscription
3. **Select subscription type** from the dropdown
4. **Choose start date** (defaults to today)
5. **Select payment method**
6. **Enable/disable auto-renewal**
7. **Click "Add Subscription"**

### Managing Existing Subscriptions

1. **Click the orange credit card button** next to customers with active subscriptions
2. **Renew subscription** with same or different type
3. **Update payment information**
4. **Cancel subscription** if needed

### Monitoring Subscriptions

1. **Visit Subscription Dashboard** for overview
2. **View expiring subscriptions** (within 7 days)
3. **Check payment due alerts**
4. **Monitor subscription statistics**

### Managing Subscription Types

1. **Navigate to Subscription Types page**
2. **Add new subscription packages**
3. **Configure pricing and meal limits**
4. **Set service hours and beverage options**

## API Endpoints

### Get Subscription Alerts
```
GET /src/services/customers/get_subscription_alerts.php?type={type}&limit={limit}
```

Types available:
- `all` - Summary statistics
- `expiring_soon` - Subscriptions expiring within 7 days
- `payment_due` - Subscriptions with payment due
- `expired` - Expired subscriptions
- `recent_alerts` - Recent system alerts

### Manage Subscriptions
```
POST /src/services/customers/manage_subscription.php
```

Actions available:
- `add_subscription` - Add new subscription
- `renew_subscription` - Renew existing subscription
- `cancel_subscription` - Cancel subscription
- `update_payment` - Update payment information

## Alert System

### Automatic Alerts
The system automatically creates alerts for:
- **7 days before expiry** - Renewal reminders
- **Payment due dates** - Payment reminders
- **Subscription cancellations** - Cancellation confirmations

### Alert Types
- **Expiring Soon**: Warning badges in customer table
- **Payment Due**: Red badges for urgent payments
- **Expired**: Danger badges for expired subscriptions
- **Renewal Reminder**: Info badges for upcoming renewals

## Customization

### Adding New Subscription Types
1. **Use the Subscription Types page** to add new packages
2. **Configure pricing** and duration
3. **Set meal limits** and service hours
4. **Enable/disable features** like beverages

### Modifying Alert Thresholds
Edit the alert creation logic in `manage_subscription.php`:
```php
// Change from 7 days to custom threshold
$alert_date = date('Y-m-d', strtotime($end_date . ' - 14 days'));
```

### Custom Payment Methods
Add new payment methods to the enum in the database:
```sql
ALTER TABLE customers MODIFY COLUMN payment_method 
ENUM('cash', 'card', 'mobile_money', 'bank_transfer', 'crypto') DEFAULT NULL;
```

## Reporting

### Subscription Analytics
- Total active subscriptions
- Revenue from subscriptions
- Customer retention rates
- Popular subscription types

### Export Functionality
The subscription dashboard includes export capabilities for:
- Customer subscription lists
- Payment history
- Usage statistics
- Alert reports

## Security Features

- **SQL Injection Protection**: All queries use prepared statements
- **Input Validation**: Comprehensive validation of all form inputs
- **Session Management**: Secure session handling
- **Access Control**: Role-based access (can be implemented)

## Troubleshooting

### Common Issues

1. **Subscription not showing as active**
   - Check if `subscription_status` is set to 'active'
   - Verify `subscription_end_date` is in the future

2. **Alerts not appearing**
   - Check `subscription_alerts` table for entries
   - Verify alert dates are correct

3. **Payment not recorded**
   - Check `subscription_payments` table
   - Verify transaction references are unique

### Database Maintenance
```sql
-- Check for expired subscriptions that weren't updated
SELECT * FROM customers 
WHERE subscription_status = 'active' 
AND subscription_end_date < CURDATE();

-- Clean up old alerts
DELETE FROM subscription_alerts 
WHERE alert_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY);
```

## Future Enhancements

- **SMS/Email Notifications**: Automated customer communications
- **Mobile App Integration**: Customer self-service portal
- **Advanced Analytics**: Detailed usage patterns and insights
- **Integration with POS**: Automatic meal tracking
- **Loyalty Program**: Enhanced points system for subscribers

## Support

For technical support or feature requests, please refer to the system documentation or contact the development team.

---

**Note**: This system is designed to work with the existing restaurant management infrastructure. Ensure all database connections and configurations are properly set up before implementation.
