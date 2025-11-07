# Service Ticket File Upload Implementation

## Overview
Added premium file upload functionality to the Service Ticket module with support for images and documents. Files can be uploaded when creating tickets and adding comments, and are displayed in all detail views.

## Changes Made

### 1. Database
- **Migration**: `2025_11_06_203232_create_service_ticket_files_table.php`
  - Created `service_ticket_files` table with columns:
    - `service_ticket_id` (foreign key)
    - `file_name`, `file_path`, `file_type`, `file_size`
    - `uploaded_by` (foreign key to users)
    - timestamps

### 2. Models
- **ServiceTicketFile.php** (New)
  - Fillable fields for file information
  - Relationships: `ticket()`, `uploader()`

- **ServiceTicket.php** (Updated)
  - Added `files()` relationship

### 3. Controller
- **ServiceTicketController.php** (Updated)
  - `store()`: Added file upload handling (multiple files)
  - `addComment()`: Added file upload handling
  - `showDetails()`: Load files with uploader info
  - `showAdminDetails()`: Load files with uploader info
  - `deleteFile()`: New method to delete files

### 4. Routes
- **web.php** (Updated)
  - Added: `DELETE service-tickets/files/{file}` route

### 5. Views

#### tickets-tab.blade.php (Updated)
- Added `enctype="multipart/form-data"` to form
- Premium file upload UI with drag-and-drop zone
- File preview with icons based on file type
- JavaScript for file handling and removal
- Supports: JPG, JPEG, PNG, PDF, DOC, DOCX, XLS, XLSX, TXT (Max 10MB each)

#### details.blade.php (Updated)
- Files display section with premium card grid
- Image preview for image files
- File type icons for documents
- Download button for each file
- File upload in comment form
- Responsive grid layout

#### admin-details.blade.php (Updated)
- Same file display section as details view
- Shows all attached files with uploader information

## Features

### Premium UI Elements
1. **File Upload Zone**
   - Gradient background with hover effects
   - Click to upload functionality
   - Visual feedback on hover
   - File type and size restrictions displayed

2. **File Preview Cards**
   - Grid layout (responsive)
   - Image thumbnails for photos
   - Icon-based preview for documents
   - File metadata (size, uploader name)
   - Smooth hover animations
   - Download action button

3. **File List (During Upload)**
   - Shows selected files before submission
   - File type icons
   - File size display
   - Remove file option
   - Clean, modern design

### File Support
- **Images**: JPG, JPEG, PNG, GIF
- **Documents**: PDF, DOC, DOCX, XLS, XLSX, TXT
- **Max Size**: 10MB per file
- **Multiple Files**: Yes

### Storage
- Files stored in: `storage/app/public/service_tickets/`
- File naming: `timestamp_originalname`
- Public access via: `storage/service_tickets/filename`

## Usage

### Creating a Ticket with Files
1. Fill in ticket details
2. Click the file upload zone or drag files
3. Review selected files
4. Submit form

### Adding Files via Comments
1. Add comment text
2. Attach files using the upload zone
3. Submit comment with files

### Viewing Files
- All files displayed in detail views
- Images show thumbnails
- Documents show type-specific icons
- Click download icon to download

## Security
- File type validation (MIME type checking)
- File size limit (10MB)
- User authentication required
- Files linked to uploader

## Notes
- Existing data is preserved (no changes to existing tickets)
- Files are optional (not required)
- Multiple files can be uploaded at once
- Files are automatically deleted when ticket is deleted (cascade)
