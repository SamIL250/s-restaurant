# 🏪 Inventory Price Management System

## Overview
This system allows you to handle fluctuating market prices for inventory items while maintaining accurate cost tracking and inventory valuation.

## 🎯 **Key Features**

### **1. Flexible Price Management**
- **New Unit Cost**: Specify different prices for new stock movements
- **Cost Update Options**: Choose whether to update the base inventory cost
- **Multiple Methods**: Replace cost or calculate weighted average
- **Value Protection**: Prevent existing stock from being devalued by cheaper new stock

### **2. Smart Cost Calculations**
- **Movement-Level Costs**: Each stock movement can have its own unit cost
- **Inventory-Level Costs**: Base cost can be updated or kept separate
- **Weighted Averages**: Automatically calculate blended costs for mixed inventory
- **Smart Protection**: Automatically prevents cost decreases when protection is enabled

### **3. Audit Trail**
- **Complete History**: All price changes are recorded in stock movements
- **Reason Tracking**: Document why prices changed (market fluctuations, supplier changes, etc.)
- **User Accountability**: Track who made price changes and when
- **Protection Logging**: Record when value protection was applied

## 🔧 **How It Works**

### **Scenario 1: Market Price Increase (Carrots)**
```
Current Situation:
- Carrots: 100 kg in stock at 100 Frw/kg
- Market price increased to 120 Frw/kg
- You buy 50 kg more at new price

Options:
1. **Keep Separate Costs** (Recommended for tracking)
   - Current stock: 100 kg × 100 Frw = 10,000 Frw
   - New stock: 50 kg × 120 Frw = 6,000 Frw
   - Total value: 16,000 Frw
   - Base cost remains: 100 Frw/kg

2. **Update to New Price**
   - Base cost becomes: 120 Frw/kg
   - All inventory valued at new price

3. **Weighted Average**
   - New base cost: (10,000 + 6,000) ÷ 150 kg = 106.67 Frw/kg
   - Blended cost reflecting both prices
```

### **Scenario 2: Market Price Decrease**
```
Current Situation:
- Rice: 200 kg in stock at 820 Frw/kg
- Market price dropped to 750 Frw/kg
- You buy 100 kg more at new price

Options:
1. **Keep Separate Costs**
   - Maintain current valuation
   - New stock recorded at lower price
   - Good for profit margin analysis

2. **Weighted Average**
   - New base cost: (164,000 + 75,000) ÷ 300 kg = 796.67 Frw/kg
   - Reflects market reality
```

## 📋 **Step-by-Step Usage**

### **1. Update Stock with New Price**
1. Click "Update Stock" on any inventory item
2. Select movement type (usually "Stock In")
3. Enter quantity
4. **Enter new unit cost** (leave empty to use current)
5. Choose whether to update inventory cost
6. **Select cost update method** if updating:
   - **Replace**: Change base cost to new price
   - **Weighted Average**: Calculate blended cost
7. **Choose protection level**:
   - **Yes - Never decrease current cost** (Recommended)
   - **No - Allow cost decreases** (Use carefully)
8. Add reason (e.g., "Market price increased to 120 Frw/kg")
9. Review the cost preview to see exactly what will happen
10. Submit

### **2. Cost Update Methods**

#### **Replace Current Cost**
- **When to use**: Complete price change, supplier change
- **Effect**: All inventory valued at new price
- **Example**: "Supplier changed, new base price is 120 Frw/kg"

#### **Weighted Average**
- **When to use**: Gradual price changes, mixed inventory
- **Effect**: Blended cost reflecting both old and new prices
- **Example**: "Market price increased, calculating blended cost"

### **3. Cost Preview System**

#### **Smart Warnings**
- **⚠️ Cost Decrease Warning**: Shows when new price would devalue existing stock
- **🛡️ Protection Applied**: Indicates when value protection will be used
- **📊 Impact Calculation**: Shows exact financial impact of changes

#### **Preview Examples**
```
✅ Normal Update:
"New base cost will be: 120.00 Frw"

⚠️ Protected Update:
"New cost (80.00 Frw) is lower than current (100.00 Frw).
Existing stock value will be protected. New stock will be recorded 
at 80.00 Frw, but base cost remains 100.00 Frw."

📊 Weighted Average:
"Weighted average cost will be: 106.67 Frw
(Current: 100 × 100 + New: 50 × 120)"
```

### **4. Best Practices**

#### **For Price Increases**
- Use **weighted average** for gradual changes
- Use **replace** for supplier changes
- Document reason clearly

#### **For Price Decreases**
- Consider **weighted average** to reflect market reality
- Use **separate costs** for profit analysis
- Monitor impact on margins

#### **For Regular Updates**
- Use **separate costs** to maintain historical data
- Update base cost periodically (monthly/quarterly)
- Keep detailed reasons for audit purposes

## 💰 **Financial Impact Examples**

### **Example 1: Carrots Price Increase**
```
Before:
- Stock: 100 kg × 100 Frw = 10,000 Frw
- Base cost: 100 Frw/kg

After adding 50 kg at 120 Frw:
- New stock: 50 kg × 120 Frw = 6,000 Frw
- Total value: 16,000 Frw

With weighted average:
- New base cost: 16,000 ÷ 150 kg = 106.67 Frw/kg
- Impact: +6.67 Frw/kg on existing inventory
```

### **Example 2: Rice Price Decrease**
```
Before:
- Stock: 200 kg × 820 Frw = 164,000 Frw
- Base cost: 820 Frw/kg

After adding 100 kg at 750 Frw:
- New stock: 100 kg × 750 Frw = 75,000 Frw
- Total value: 239,000 Frw

With weighted average:
- New base cost: 239,000 ÷ 300 kg = 796.67 Frw/kg
- Impact: -23.33 Frw/kg on existing inventory
```

## 🔍 **Monitoring and Reporting**

### **Stock Movement History**
- View all price changes in inventory movements
- Track cost evolution over time
- Identify patterns in price fluctuations

### **Inventory Valuation**
- Real-time total inventory value
- Cost basis for each item
- Impact of price changes on overall inventory

### **Profit Analysis**
- Compare movement costs vs. base costs
- Identify items with significant price changes
- Plan for future price adjustments

## ⚠️ **Important Considerations**

### **1. Data Integrity**
- All price changes are recorded
- No historical data is lost
- Complete audit trail maintained

### **2. Financial Reporting**
- Choose cost method based on accounting needs
- Consider tax implications of cost changes
- Maintain consistency in valuation methods

### **3. Business Decisions**
- Use price data for supplier negotiations
- Plan inventory purchases based on trends
- Adjust pricing strategies accordingly

## 🚀 **Getting Started**

1. **Review Current Inventory**: Identify items with significant price variations
2. **Set Update Policies**: Decide when to update base costs
3. **Train Staff**: Ensure proper use of new features
4. **Monitor Results**: Track impact on inventory valuation
5. **Adjust Strategy**: Refine approach based on business needs

This system gives you complete control over inventory pricing while maintaining accurate financial records and providing valuable insights for business decision-making.

## 🔒 **Value Protection System**

### **Why Protect Existing Stock Value?**
When you buy new stock at a lower price, you don't want to devalue your existing inventory that you paid more for. This protection prevents financial losses on previous purchases.

### **How Protection Works**
- **Enabled by Default**: Protection is automatically enabled to prevent accidental value loss
- **Smart Detection**: System automatically detects when new cost would decrease existing value
- **Flexible Control**: You can choose to allow cost decreases when needed
- **Clear Feedback**: System shows exactly what will happen before you commit

### **Protection Scenarios**

#### **Scenario 1: Carrots Price Drop (Protected)**
```
Current Situation:
- Carrots: 100 kg in stock at 100 Frw/kg (Value: 10,000 Frw)
- Market price dropped to 80 Frw/kg
- You buy 50 kg more at new price

With Protection Enabled:
- New stock: 50 kg × 80 Frw = 4,000 Frw
- Existing stock: 100 kg × 100 Frw = 10,000 Frw
- Total value: 14,000 Frw
- Base cost remains: 100 Frw/kg (Protected!)
- Result: You don't lose money on existing stock
```

#### **Scenario 2: Rice Price Drop (Unprotected)**
```
Current Situation:
- Rice: 200 kg in stock at 820 Frw/kg (Value: 164,000 Frw)
- Market price dropped to 750 Frw/kg
- You buy 100 kg more at new price

With Protection Disabled:
- New stock: 100 kg × 750 Frw = 75,000 Frw
- Weighted average: (164,000 + 75,000) ÷ 300 kg = 796.67 Frw/kg
- Existing stock devalued: 200 kg × (796.67 - 820) = -4,666 Frw
- Result: You lose money on existing inventory
```

### **When to Use Protection**

#### **✅ Always Protect (Recommended)**
- **Regular Business**: Prevent losses on existing inventory
- **Market Fluctuations**: Handle temporary price drops
- **Supplier Changes**: Maintain value of current stock
- **Financial Planning**: Keep predictable inventory values

#### **❌ Disable Protection (Use Carefully)**
- **Complete Reset**: Starting fresh with new pricing
- **Market Reality**: When you must reflect true current costs
- **Accounting Requirements**: Specific financial reporting needs
- **Strategic Decisions**: Deliberate cost reduction strategy
