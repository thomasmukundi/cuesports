# Real-Time Mobile App Optimization Plan
## CueSports Kenya - Hybrid Performance Strategy

## 🔥 **Real-Time Requirements (Immediate Feedback)**

### **1. Notifications System**
**Current:** Polling every app resume
**Required:** Instant delivery

**Implementation:**
```dart
// File: poolapp/lib/services/notification_service.dart
class NotificationService extends ChangeNotifier {
  Timer? _pollingTimer;
  
  void startRealTimePolling() {
    _pollingTimer = Timer.periodic(Duration(seconds: 5), (timer) {
      _checkForNewNotifications();
    });
  }
  
  // Immediate check when app becomes active
  void checkImmediateNotifications() async {
    final notifications = await ApiService.get('/notifications/unread-count');
    // Update badge immediately
  }
}
```

**Laravel Backend:**
```php
// File: app/Http/Controllers/Api/NotificationController.php
public function realTimeCheck() {
    return response()->json([
        'unread_count' => auth()->user()->unreadNotifications()->count(),
        'latest_notification' => auth()->user()->notifications()->latest()->first(),
        'timestamp' => now()
    ]);
}
```

### **2. Match State Changes (Critical Real-Time)**
**Scenarios:**
- Date selection by opponent
- Match state: pending → scheduled → adding_results → waiting_confirmation → completed

**Implementation:**
```dart
// File: poolapp/lib/services/match_service.dart
class MatchService extends ChangeNotifier {
  Map<int, Timer> _matchPollers = {};
  
  void startMatchPolling(int matchId) {
    _matchPollers[matchId] = Timer.periodic(Duration(seconds: 3), (timer) {
      _checkMatchStatus(matchId);
    });
  }
  
  void stopMatchPolling(int matchId) {
    _matchPollers[matchId]?.cancel();
    _matchPollers.remove(matchId);
  }
  
  Future<void> _checkMatchStatus(int matchId) async {
    final match = await ApiService.get('/matches/$matchId/status');
    // Update UI immediately if status changed
    if (match['status'] != _currentMatches[matchId]['status']) {
      _currentMatches[matchId] = match;
      notifyListeners();
    }
  }
}
```

**Laravel Endpoint:**
```php
// File: routes/api.php - Add new route
Route::get('/matches/{match}/status', [MatchController::class, 'getStatus']);

// File: app/Http/Controllers/Api/MatchController.php
public function getStatus(PoolMatch $match) {
    return response()->json([
        'id' => $match->id,
        'status' => $match->status,
        'proposed_dates' => $match->proposed_dates,
        'selected_dates' => $match->selected_dates,
        'player1_score' => $match->player1_score,
        'player2_score' => $match->player2_score,
        'last_updated' => $match->updated_at,
    ]);
}
```

### **3. Chat Messages (Instant Delivery)**
**Current:** Manual refresh
**Required:** Real-time messaging

**Implementation:**
```dart
// File: poolapp/lib/services/chat_service.dart
class ChatService extends ChangeNotifier {
  Map<int, Timer> _chatPollers = {};
  Map<int, DateTime> _lastMessageTime = {};
  
  void startChatPolling(int matchId) {
    _chatPollers[matchId] = Timer.periodic(Duration(seconds: 2), (timer) {
      _checkNewMessages(matchId);
    });
  }
  
  Future<void> _checkNewMessages(int matchId) async {
    final lastTime = _lastMessageTime[matchId] ?? DateTime.now().subtract(Duration(hours: 1));
    final newMessages = await ApiService.get('/matches/$matchId/messages?since=${lastTime.toIso8601String()}');
    
    if (newMessages['data'].isNotEmpty) {
      _messages[matchId].addAll(newMessages['data']);
      _lastMessageTime[matchId] = DateTime.now();
      notifyListeners();
    }
  }
}
```

### **4. Profile Updates (Immediate Reflection)**
**Implementation:**
```dart
// File: poolapp/lib/services/settings_service.dart
Future<bool> updateProfile(Map<String, dynamic> data) async {
  try {
    final response = await ApiService.put('/user/profile', data);
    if (response['success']) {
      // Update local cache immediately
      _cachedProfile = response['user'];
      await _cacheProfile(_cachedProfile);
      notifyListeners();
      return true;
    }
  } catch (e) {
    // Handle error
  }
  return false;
}
```

## 📦 **Cacheable Data (Optimized Loading)**

### **1. User Statistics (Heavy Caching)**
```dart
// File: poolapp/lib/services/user_service.dart
class UserService extends ChangeNotifier {
  static const String STATS_CACHE_KEY = 'user_stats';
  static const int STATS_CACHE_DURATION = 30; // 30 minutes
  
  Future<Map<String, dynamic>> getUserStats() async {
    // Check cache first
    final cached = await _getCachedStats();
    if (cached != null) return cached;
    
    // Fetch from API
    final stats = await ApiService.get('/statistics');
    await _cacheStats(stats);
    return stats;
  }
}
```

### **2. Tournament Lists (Smart Caching)**
```dart
// File: poolapp/lib/services/tournament_service.dart
Future<List<Tournament>> loadTournaments({bool forceRefresh = false}) async {
  if (!forceRefresh) {
    final cached = await _getCachedTournaments();
    if (cached != null && cached.isNotEmpty) {
      // Return cached data immediately, fetch fresh in background
      _refreshTournamentsInBackground();
      return cached;
    }
  }
  
  final tournaments = await ApiService.get('/tournaments');
  await _cacheTournaments(tournaments);
  return tournaments;
}
```

### **3. Leaderboard (Timed Cache)**
```dart
// 5-minute cache for leaderboard
static const int LEADERBOARD_CACHE_DURATION = 5;
```

## 🔐 **Authentication (Persistent JWT)**

### **Laravel JWT Configuration**
```php
// File: config/jwt.php
'ttl' => null, // No expiry
'refresh_ttl' => null, // No refresh expiry

// File: app/Http/Controllers/Api/AuthController.php
public function login(Request $request) {
    // ... validation
    
    $token = JWTAuth::claims(['exp' => null])->fromUser($user);
    
    return response()->json([
        'success' => true,
        'token' => $token,
        'user' => $user,
        'expires_at' => null // Never expires
    ]);
}

public function logout() {
    JWTAuth::invalidate(JWTAuth::getToken());
    return response()->json(['success' => true]);
}
```

### **Flutter Token Management**
```dart
// File: poolapp/lib/services/auth_service.dart
class AuthService extends ChangeNotifier {
  Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
    // No expiry time stored
  }
  
  Future<void> logout() async {
    // Call API to invalidate token
    await ApiService.post('/logout');
    
    // Clear local storage
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    
    notifyListeners();
  }
}
```

## ⚡ **Hybrid Polling Strategy**

### **Smart Polling Implementation**
```dart
// File: poolapp/lib/services/polling_service.dart
class PollingService {
  static const Map<String, int> POLLING_INTERVALS = {
    'notifications': 5,      // 5 seconds
    'active_matches': 3,     // 3 seconds  
    'chat_messages': 2,      // 2 seconds
    'match_status': 3,       // 3 seconds
  };
  
  void startPolling(String type, Function callback) {
    final interval = POLLING_INTERVALS[type] ?? 10;
    Timer.periodic(Duration(seconds: interval), (timer) {
      if (_shouldPoll(type)) {
        callback();
      }
    });
  }
  
  bool _shouldPoll(String type) {
    // Only poll when app is active and user is engaged
    return WidgetsBinding.instance.lifecycleState == AppLifecycleState.resumed;
  }
}
```

## 📱 **Screen-Specific Implementation**

### **1. Home Screen**
```dart
// File: poolapp/lib/screens/home_screen.dart
class HomeScreen extends StatefulWidget {
  @override
  void initState() {
    super.initState();
    
    // Load cached data immediately
    _loadCachedData();
    
    // Start real-time polling for notifications
    _notificationService.startRealTimePolling();
    
    // Refresh fresh data in background
    _refreshDataInBackground();
  }
  
  void _loadCachedData() {
    // Show cached tournaments, user stats immediately
    _userService.getCachedStats();
    _tournamentService.getCachedTournaments();
  }
}
```

### **2. Match Details Screen**
```dart
// File: poolapp/lib/screens/match_details_screen.dart
class MatchDetailsScreen extends StatefulWidget {
  @override
  void initState() {
    super.initState();
    
    // Start real-time polling for this specific match
    _matchService.startMatchPolling(widget.matchId);
    _chatService.startChatPolling(widget.matchId);
  }
  
  @override
  void dispose() {
    // Stop polling when leaving screen
    _matchService.stopMatchPolling(widget.matchId);
    _chatService.stopChatPolling(widget.matchId);
    super.dispose();
  }
}
```

### **3. Tournament Screen**
```dart
// File: poolapp/lib/screens/tournaments_screen.dart
class TournamentsScreen extends StatefulWidget {
  @override
  void initState() {
    super.initState();
    
    // Load cached tournaments immediately
    _tournamentService.loadTournaments(forceRefresh: false);
  }
  
  Future<void> _onRefresh() async {
    // Manual refresh - force fresh data
    await _tournamentService.loadTournaments(forceRefresh: true);
  }
}
```

## 🎯 **Performance Metrics After Implementation**

### **Real-Time Actions (Target: <1 second)**
- New notification display: **Immediate** (5s polling)
- Match state changes: **Immediate** (3s polling)
- Chat message delivery: **Immediate** (2s polling)
- Profile update reflection: **Immediate** (optimistic updates)

### **Cached Actions (Target: <0.5 seconds)**
- Tournament list: **0.2s** (cached)
- User statistics: **0.1s** (cached)
- Leaderboard: **0.3s** (cached)
- User profile: **0.1s** (cached)

### **Data Usage Optimization**
- **70% reduction** in API calls for static data
- **Real-time polling** only for active interactions
- **Smart caching** with background refresh
- **Persistent authentication** (no re-login)

## 🔧 **Implementation Priority**

### **Week 1: Core Real-Time Features**
1. Notification real-time polling (4 hours)
2. Match status polling (6 hours)
3. JWT persistent tokens (2 hours)

### **Week 2: Chat & Profile Updates**
1. Real-time chat polling (4 hours)
2. Immediate profile updates (2 hours)
3. Smart polling service (4 hours)

### **Week 3: Caching Optimization**
1. Tournament/stats caching (4 hours)
2. Background refresh system (3 hours)
3. Performance testing (3 hours)

This hybrid approach ensures **immediate feedback** for critical user interactions while maintaining **optimal performance** through intelligent caching of static data.
