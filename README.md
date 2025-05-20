# Project Name: Swing Trading Dashboard

## Project Overview:
This project is a Swing Trading Dashboard for managing trades, tracking profit and loss, and integrating live market data via APIs.

## Features:
- Add, edit, and delete trades
- Track capital, PnL, and ROI
- Live updates of LTP (Last Traded Price) from stock exchanges
- Reports on trades and performance

## Technologies:
- PHP (backend)
- MySQL (database)
- JavaScript, jQuery (frontend)
- Bootstrap (UI framework)
- TradingView API for stock charts

## Database Setup:
### Tables:
- `settings` (stores configuration settings like API keys, user details)
- `trades` (stores trade information)
- `instruments` (stores stock/ETF symbols and related data)
- `kite_tokens` (stores API tokens for Kite Connect)
- `partial_bookings` (stores partial trade bookings)

## Installation:
1. Run `install.php` on your server to set up the database.
2. Provide your database credentials in the installation form.
3. The tables will be automatically created, and default settings will be inserted.

## API Integration:
- **Kite Connect**: API keys and access tokens are required to fetch real-time data.
- **TradingView**: Embedded charts for stock symbols.

## Usage:
1. Add trades using the form on the dashboard.
2. Filter and track active trades.
3. View reports and analyze performance.

## License:
This project is licensed under the MIT License - see the LICENSE file for details.
