# Amelia Slots Blocker

A WordPress plugin designed to enhance Amelia Booking's slot management capabilities with advanced scheduling controls and participant-based filtering.

## Features

### Early Morning Slots Management
- Configure minimum advance booking times for early morning appointments
- Set specific time slots (e.g., 6:10 AM) with customizable booking rules
- Optional secondary time slot with independent rules
- Automatic 2-minute buffer added to configured times

### Participant-Based Slot Filtering
- Set minimum time margins for immediate bookings
- Configure a placeholder customer ID for participant counting
- Smart filtering based on existing participants
- Exclude placeholder customer from participant counts

### Manual Slot Blocking
- Block specific time slots on selected dates
- Calendar-based interface for easy date selection
- Add optional reasons for blocks
- View and manage all active blocks

## Requirements
- WordPress 5.0 or higher
- Amelia Booking Plugin
- PHP 7.2 or higher

## Installation

1. Upload the plugin files to `/wp-content/plugins/amelia-slots-blocker`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure the plugin settings under 'Hrs Madrugada' in the admin menu

## Configuration

### Early Morning Settings
1. Navigate to 'Hrs Madrugada' in the admin menu
2. Set minimum advance hours for early morning slots
3. Select target time slot
4. Optionally enable and configure secondary time slot

### Participant Settings
1. Go to 'Cliente Iniciador' submenu
2. Search and select the placeholder customer
3. Configure minimum minutes for immediate slots

### Manual Blocks
1. Access 'Bloqueos' submenu
2. Select date and time slot
3. Add optional reason for blocking
4. Save to apply the block

## Support
For support or feature requests, please use the [GitHub issues page](https://github.com/4REMi/slotsBlocker_AmeliaBooking/issues).

## Version History

### 1.0.1
- Separated settings groups to prevent conflicts
- Fixed calendar display in blocks page
- Improved placeholder customer management
- Updated plugin information and branding

## Author
Rocash

## License
This project is licensed under the GPL v2 or later 
