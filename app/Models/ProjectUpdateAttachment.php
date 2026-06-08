<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectUpdateAttachment extends Model
{
    protected $table = 'project_update_attachments'; // Specify the table name
    
    protected $fillable = [
        'project_update_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size'
    ];

    protected $casts = [
        'file_size' => 'integer'
    ];

    public function projectUpdate()
    {
        return $this->belongsTo(ProjectUpdate::class);
    }

    public function isImage()
    {
        return str_starts_with($this->file_type, 'image/');
    }

    public function getFileSizeFormatted()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}