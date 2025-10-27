# 💰 Finova - Modern Financial Management Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](http://makeapullrequest.com)
[![GitHub Stars](https://img.shields.io/github/stars/mehmetmasa/finova?style=social)](https://github.com/mehmetmasa/finova)

**Finova** is an open-source, AI-powered financial management platform built with Laravel. It combines comprehensive financial tracking, CRM, project management, and intelligent AI assistance in one powerful system.

> 🚀 **Perfect for:** Freelancers, Small Businesses, Agencies, and Financial Professionals

## 🎯 Live Demo

Try it out without installation:
- **Demo URL:** [https://finans.mikpa.com](https://finans.mikpa.com)
- **Admin Login:** Use demo credentials on the login page
- **Employee Login:** Test employee role features

> 💡 **Note:** Demo resets every 30 minutes.


## ✨ Features

### 💳 **Financial Management**
- [x] **Multi-Currency Support** - Track finances in multiple currencies with automatic conversion
- [x] **Account Management** - Support for bank accounts, credit cards, crypto wallets, virtual POS, and cash
- [x] **Transaction Tracking** - Comprehensive income, expense, and transfer management
- [x] **Installment & Subscription** - Automatic recurring transaction management
- [x] **Tax & Withholding** - Built-in tax calculation and withholding management

### 👥 **Customer & Supplier Management**
- [x] **Customer Management** - Complete customer database with contact information and history
- [x] **Supplier Management** - Supplier tracking with debt and payment management
- [x] **Lead Management** - Lead tracking and conversion system
- [x] **Customer Agreements** - Contract and agreement management
- [x] **Customer Credentials** - Secure storage of sensitive customer information

### 🏗️ **Project Management**
- [x] **Project Tracking** - Create and manage projects with boards and task lists
- [x] **Task Management** - Kanban-style task management with labels and assignments

### 📊 **Planning & Analytics**
- [x] **Savings Plans** - Goal-based savings tracking
- [x] **Investment Plans** - Investment portfolio management
- [x] **Debt Management** - Loan and debt tracking with payment schedules
- [x] **Commission System** - Agent commission tracking and management

### 🤖 **AI Integration**
- [x] **AI Assistant** - Integrated AI chat assistant for financial insights
- [x] **Document Analysis** - AI-powered document processing and analysis
- [x] **Smart Suggestions** - AI recommendations for financial decisions
- [x] **Multi-Provider Support** - OpenAI and Google Gemini integration

### 🔐 **User Management & Security**
- [x] **Role-Based Access Control** - Comprehensive role and permission management system
- [x] **Custom Roles** - Create and manage custom roles with specific permissions
- [x] **Permission Management** - Granular permission control for all system features
- [x] **User Management** - Create, edit, and manage team members with role assignments

### 📱 **Modern UI/UX**
- [x] **Livewire Components** - Reactive, modern user interface
- [x] **Responsive Design** - Mobile-first responsive design
- [x] **Real-time Updates** - Live data updates and notifications

## 🚀 Roadmap & Upcoming Features

### 🔶 Enhanced Multi-Language Support
- [ ] **Complete Translation System** - Full translation coverage for all UI elements
- [ ] **Dynamic Language Switching** - Change language without page refresh
- [ ] **RTL Support** - Right-to-left language support (Arabic, Hebrew)
- [ ] **Custom Translation Management** - Admin panel for managing translations

### 🔶 Advanced Currency Management
- [ ] **Default Currency Selection** - Set preferred currency
- [ ] **Currency Rate Auto-Update** - Automatic exchange rate updates from APIs
- [ ] **Custom Exchange Rates** - Manual override for specific rates
- [ ] **Currency Formatting** - Localized number and currency formatting

### 🔶 Automated Setup Wizard
- [ ] **One-Click Installation** - Streamlined setup process
- [ ] **Database Configuration** - Interactive database setup
- [ ] **Demo Data Installation** - Optional sample data loading
- [ ] **AI Configuration** - Easy API key setup for AI features
- [ ] **Email & Notification Setup** - Configure SMTP and notifications

> 💡 **Want to contribute?** Check out our [Contributing Guide](#-contributing) and pick a feature to work on!

## 🛠️ Technology Stack

### Backend
- **Laravel 11** - PHP web framework
- **MySQL/PostgreSQL** - Database
- **Livewire 3** - Reactive components
- **Filament 3** - Admin panel

### Frontend
- **Tailwind CSS** - Utility-first CSS framework
- **Alpine.js** - Lightweight JavaScript framework
- **Chart.js** - Data visualization
- **TipTap Editor** - Rich text editing

### AI & External Services
- **OpenAI API** - Chat and document analysis
- **Google Gemini** - Alternative AI provider
- **Telegram Bot API** - Notification system

### Development Tools
- **Vite** - Fast build tool
- **Pint** - PHP code style fixer
- **PHPStan** - Static analysis
- **Pest** - PHP testing framework

## 📋 Requirements

- **PHP** 8.2 or higher
- **Composer** - PHP dependency manager
- **Node.js** 18.x or higher
- **NPM** or **Yarn** - JavaScript dependency manager
- **MySQL** 5.7+ or **PostgreSQL** 10+
- **Redis** (optional, for caching and queues)

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/mehmetmasa/finova.git
cd finova
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install JavaScript Dependencies
```bash
npm install
# or
yarn install
```

### 4. Environment Configuration
```bash
cp .env.example .env
```

Edit the `.env` file and configure:
- Database credentials (DB_DATABASE, DB_USERNAME, DB_PASSWORD)
- Mail configuration (for notifications)
- AI API keys (OpenAI, Gemini)
- Application settings

### 5. Generate Application Key
```bash
php artisan key:generate
```

### 6. Database Setup
```bash
php artisan migrate --seed
```

This will create all database tables and populate with sample data.

### 7. Storage Setup
```bash
php artisan storage:link
```

### 8. Build Frontend Assets
```bash
npm run build
# For development
npm run dev
```

### 9. Start the Application
```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## 🔑 Default Credentials

**Admin User:**
- Email: `admin@admin.com`
- Password: `admin123`

## 📖 Usage

### Dashboard
- Overview of financial status
- Recent transactions and activities
- Quick action buttons for common tasks

### Financial Management
- **Transactions**: Add, edit, and categorize financial transactions
- **Accounts**: Manage different account types and balances
- **Categories**: Organize transactions with custom categories
- **Budgets**: Set and track spending limits

### Customer Management
- **Customers**: Manage customer information and history
- **Leads**: Track potential customers and conversion
- **Agreements**: Manage contracts and recurring agreements

### Project Management
- **Projects**: Create and manage projects
- **Boards**: Kanban-style project boards
- **Tasks**: Task management with assignments and due dates

### AI Assistant
- Access AI chat assistant for financial insights
- Upload and analyze documents
- Get AI-powered recommendations

## 🔧 Configuration

### AI Integration Setup
1. Get API keys from [OpenAI](https://platform.openai.com) or [Google AI](https://ai.google.dev)
2. Add keys to `.env` file:
```env
OPENAI_API_KEY=your_openai_key
GEMINI_API_KEY=your_gemini_key
```

### Telegram Notifications (Optional)
1. Create a Telegram bot via [@BotFather](https://t.me/botfather)
2. Add bot token to `.env`:
```env
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id
```

## 🤝 Contributing

**We love contributions!** Whether it's bug fixes, new features, or documentation improvements, all contributions are welcome!

### How to Contribute

1. **Fork** the repository
2. **Clone** your fork: `git clone https://github.com/YOUR_USERNAME/finova.git`
3. **Create** a feature branch: `git checkout -b feature/amazing-feature`
4. **Make** your changes
5. **Commit** your changes: `git commit -m 'Add amazing feature'`
6. **Push** to the branch: `git push origin feature/amazing-feature`
7. **Open** a Pull Request

### Development Guidelines
- ✅ Follow **PSR-12** coding standards
- ✅ Use **meaningful commit messages**
- ✅ Keep PRs **focused and small**
- ✅ Add **comments** for complex logic

### Good First Issues

Look for issues labeled with:
- 🟢 `good first issue` - Perfect for first-time contributors
- 🟡 `help wanted` - We need your help!
- 🔵 `documentation` - Help improve our docs

### Questions?

- 💬 Open a [Discussion](https://github.com/mehmetmasa/finova/discussions)
- 🐛 Found a bug? [Open an Issue](https://github.com/mehmetmasa/finova/issues)
- 💡 Have an idea? [Create a Feature Request](https://github.com/mehmetmasa/finova/issues/new)

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The PHP framework
- [Livewire](https://laravel-livewire.com) - Reactive components
- [Filament](https://filamentphp.com) - Admin panel
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework
- [Alpine.js](https://alpinejs.dev) - Lightweight JavaScript framework
- [OpenAI](https://openai.com) - AI integration
- [Google Gemini](https://ai.google.dev) - AI integration


## 💬 Community & Support

### Get Help

- 📖 **Documentation:** Check out our [Wiki](https://github.com/mehmetmasa/finova/wiki)
- 💬 **Discussions:** Join [GitHub Discussions](https://github.com/mehmetmasa/finova/discussions)
- 🐛 **Bug Reports:** [Open an Issue](https://github.com/mehmetmasa/finova/issues)
- ⭐ **Feature Requests:** Share your ideas in [Issues](https://github.com/mehmetmasa/finova/issues/new)

### Show Your Support

If you find Finova helpful, please:
- ⭐ **Star** this repository
- 🐦 **Share** it on social media
- 🔗 **Link** to it from your projects
- 💬 **Tell** others about it

---

<div align="center">

**Made with ❤️ by the open-source community**

*Developed and maintained by [Mikpa](https://mikpa.com)*

[⬆ Back to Top](#-finova---modern-financial-management-platform)

</div>