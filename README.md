# Pool Tournament Backend

A comprehensive Laravel backend system for managing pool tournaments with hierarchical levels, automated progression, payment integration, and real-time notifications.

## Features

- **Hierarchical Tournament Structure**: Community → County → Regional → National levels
- **Automated Match Generation**: Smart pairing algorithm with special handling for 2-4 player groups
- **Payment Integration**: Stripe integration via Laravel Cashier for tournament registration fees
- **Real-time Notifications**: WebSocket support via Pusher for instant match updates
- **Match Scheduling**: Player preference-based scheduling system
- **Result Confirmation**: Two-player confirmation system for match results
- **Admin Dashboard**: Complete tournament management capabilities
- **Automated Progression**: Configurable automatic or manual tournament progression

## Installation

1. **Clone the repository**
```bash
git clone [repository-url]
cd cuesports
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Environment Setup**
```bash
cp .env.example .env
```

4. **Configure your `.env` file**
```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=poolapp
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Admin
ADMIN_EMAIL=admin@cuesports.com

# Tournament Settings
TOURNAMENT_AUTOMATION_MODE=manual  # or 'automatic'
TOURNAMENT_CHECK_INTERVAL=5  # minutes

# Pusher (for WebSockets)
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1

# Stripe
STRIPE_KEY=your_public_key
STRIPE_SECRET=your_secret_key
STRIPE_WEBHOOK_SECRET=your_webhook_secret
```

5. **Generate application key**
```bash
php artisan key:generate
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Seed the database (optional)**
```bash
php artisan db:seed
```

8. **Start the queue worker**
```bash
php artisan queue:work
```

9. **Run the scheduler (in production)**
```bash
# Add to crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

10. **Start the development server**
```bash
php artisan serve
```

## API Endpoints

### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout

### Tournament Registration
- `GET /api/tournaments` - List available tournaments
- `POST /api/tournaments/{id}/register` - Register for tournament
- `POST /api/tournaments/{id}/confirm-payment` - Confirm payment
- `DELETE /api/tournaments/{id}/cancel` - Cancel registration
- `GET /api/my-registrations` - List user's registrations

### Matches
- `GET /api/matches/my-matches` - Get user's matches
- `GET /api/matches/{id}` - Get match details
- `POST /api/matches/{id}/propose-dates` - Propose match dates
- `POST /api/matches/{id}/select-dates` - Select preferred dates
- `POST /api/matches/{id}/submit-results` - Submit match results
- `POST /api/matches/{id}/confirm-results` - Confirm/reject results
- `POST /api/matches/{id}/forfeit` - Report forfeit

### Player Stats
- `PUT /api/profile` - Update player profile
- `GET /api/leaderboard` - Get leaderboard
- `GET /api/players/{id}/stats` - Get player statistics
- `GET /api/my-stats` - Get own statistics

### Chat
- `GET /api/matches/{id}/messages` - Get match chat messages
- `POST /api/matches/{id}/send-message` - Send chat message
- `GET /api/conversations` - Get all conversations

### Notifications
- `GET /api/notifications` - Get all notifications
- `GET /api/notifications/unread` - Get unread notifications
- `PUT /api/notifications/{id}/read` - Mark as read
- `PUT /api/notifications/mark-all-read` - Mark all as read

### Admin Endpoints
- `GET /api/admin/tournaments` - List all tournaments
- `POST /api/admin/tournaments` - Create tournament
- `POST /api/admin/tournaments/{id}/initialize` - Initialize tournament
- `POST /api/admin/tournaments/{id}/next-round` - Generate next round
- `GET /api/admin/tournaments/{id}/check-completion` - Check completion
- `GET /api/admin/tournaments/{id}/matches` - Get tournament matches
- `GET /api/admin/tournaments/{id}/statistics` - Get statistics
- `PUT /api/admin/tournaments/{id}/automation-mode` - Update automation
- `GET /api/admin/tournaments/pending-approvals` - Get pending approvals

## Tournament Flow

1. **Registration Phase**
   - Players register and pay the tournament fee
   - Admin reviews and approves registrations

2. **Initialization**
   - Admin initializes tournament at community level
   - System groups players by location and generates matches

3. **Match Phase**
   - Players propose and agree on match dates
   - Players complete matches and submit results
   - Opponents confirm results

4. **Progression**
   - System checks for round/level completion
   - Winners advance to next round/level
   - Process continues until national champion

## Match Pairing Algorithm

### Standard Pairing (>4 players)
- Random shuffling with same-origin avoidance
- Handles odd numbers with bye matches
- Winners automatically advance

### Special Cases
- **2 Players**: Direct final match
- **3 Players**: Semi-final + final with bye
- **4 Players**: Two quarter-finals → final

## Testing

Run the test suite:
```bash
php artisan test
```

Run specific test files:
```bash
php artisan test --filter TournamentTest
php artisan test --filter MatchTest
php artisan test --filter MatchAlgorithmServiceTest
```

## Queue Jobs

The system uses Laravel queues for:
- Tournament completion checks
- Match notifications
- Prize distribution
- Email notifications

## Scheduled Tasks

The scheduler runs:
- `tournaments:check` - Every 5 minutes (configurable)
  - Checks tournament completions
  - Auto-generates next rounds (if in automatic mode)
  - Awards prizes on completion

## WebSocket Events

Real-time events broadcast:
- `match.pairing.created` - New match pairing
- `match.result.submitted` - Results need confirmation
- `tournament.completed` - Tournament finished
- `chat.message` - New chat message

## Security

- Laravel Sanctum for API authentication
- Admin middleware for protected routes
- Payment webhook signature verification
- CORS configuration for API access

## Troubleshooting

### Common Issues

1. **Migrations fail**
   - Check database connection settings
   - Ensure database exists

2. **WebSockets not working**
   - Verify Pusher credentials
   - Check firewall settings

3. **Payments not processing**
   - Verify Stripe API keys
   - Check webhook configuration

4. **Jobs not running**
   - Ensure queue worker is running
   - Check queue connection settings

## License

This project is proprietary software.

## Support

For support, contact the development team.
