# Running the store

A guide for administrators. Sign in with an administrator account and choose **Admin** in the top
navigation, or go to `/admin`.

To create the first administrator on a fresh production install:

```sh
php bin/technest user:create <username> <email> --admin    # prompts for the password
```

## Dashboard

The dashboard covers the last 30 days and compares each headline figure with the 30 days before.
Cancelled orders are excluded everywhere.

- **Revenue, orders, average order, units sold, customers** — with the change against the previous
  period.
- **Revenue per day** — hover a bar for that day's revenue and order count.
- **Revenue by category** and **best sellers**.
- **Orders by status** — each links to the filtered order list.
- **Low stock** — active products with five or fewer units.

## Orders

The order list filters by status. Open an order to see its items, shipping address and full history
— every status change with when it happened and who made it.

Orders can only move along the lifecycle:

```
pending → processing → shipped → delivered
   └──────────┴──→ cancelled
```

The status menu only offers the valid next steps. **Cancelling returns the items to stock.** A
shipped order can no longer be cancelled, and delivered or cancelled orders are final. If two
administrators change the same order at once, the second is told to reload rather than overwriting
the first.

## Products

- **New product**: name, category, base price, stock, description and an image chosen from the
  artwork that ships with the store. The URL is generated from the name unless you set one.
- **Options**: after creating a product, add option groups on its edit page — for example group
  *Storage*, choice *256GB*, extra *100.00*. Customers must choose one value from every group.
  Removing an option makes any cart holding it ask the customer to choose again.
- **Featured** products appear on the homepage.
- **Archive** hides a product from the store without deleting it, so past orders keep their lines.
  Restore it from the same button.

Prices are typed in dollars (`1199.99`, `1,199.99` and `$15` all work) and stored exactly, in cents.

## Customers

The customer list shows each account's order count and lifetime spend.

- **Disable** stops an account from signing in and signs it out on its next request. **Enable**
  reverses it.
- **Make admin / Revoke admin** grants or removes administrator access; the store records who
  granted it.

You cannot disable your own account or remove your own admin access, so the store can never be left
without an administrator.

## Themes

Choose between **Regular**, **Minimal** and **Holiday**. Each card previews the theme with its own
colours; **Apply theme** switches it for every visitor at once.

## Command line

```sh
php bin/technest db:setup            # migrate, and seed the catalog if empty
php bin/technest db:migrate          # apply pending migrations only
php bin/technest db:reset            # drop everything and start again (refused in production)
php bin/technest user:grant-admin <username>
```
