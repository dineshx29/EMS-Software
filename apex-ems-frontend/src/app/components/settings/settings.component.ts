import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="settings-container">
      <div class="page-header">
        <div class="page-title">
          <h1><i class="pi pi-cog"></i> System Settings</h1>
          <p>Configure system parameters and preferences</p>
        </div>
      </div>
      
      <div class="settings-content">
        <div class="apex-card">
          <div class="apex-card-header">
            <i class="pi pi-building"></i> Company Information
          </div>
          <div class="apex-card-body">
            <div class="setting-item">
              <label>Company Name</label>
              <input type="text" class="apex-form-control" value="APEX Corporation" readonly>
            </div>
            <div class="setting-item">
              <label>Address</label>
              <input type="text" class="apex-form-control" value="123 Business St, City, State" readonly>
            </div>
          </div>
        </div>
        
        <div class="apex-card">
          <div class="apex-card-header">
            <i class="pi pi-shield"></i> Security Settings
          </div>
          <div class="apex-card-body">
            <div class="setting-item">
              <label>Password Policy</label>
              <select class="apex-form-control">
                <option>Strong (8+ chars, mixed case, numbers, symbols)</option>
                <option>Medium (6+ chars, mixed case, numbers)</option>
                <option>Basic (6+ characters)</option>
              </select>
            </div>
            <div class="setting-item">
              <label>Session Timeout (minutes)</label>
              <input type="number" class="apex-form-control" value="30">
            </div>
          </div>
        </div>
        
        <div class="apex-card">
          <div class="apex-card-header">
            <i class="pi pi-palette"></i> Appearance
          </div>
          <div class="apex-card-body">
            <div class="setting-item">
              <label>Theme</label>
              <select class="apex-form-control">
                <option>Light (Default)</option>
                <option>Dark</option>
                <option>Auto (System)</option>
              </select>
            </div>
            <div class="setting-item">
              <label>Primary Color</label>
              <input type="color" class="apex-form-control" value="#0066cc">
            </div>
          </div>
        </div>
      </div>
      
      <div class="settings-actions">
        <button class="apex-button apex-button-success">Save Settings</button>
        <button class="apex-button apex-button-secondary">Reset to Defaults</button>
      </div>
    </div>
  `,
  styles: [`
    .settings-container {
      padding: 0;
      height: 100%;
      overflow-y: auto;
    }
    
    .page-header {
      background: var(--apex-surface);
      border: 1px solid var(--apex-border);
      border-radius: 4px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .page-title h1 {
      margin: 0;
      font-size: 1.75rem;
      font-weight: 600;
      color: var(--apex-text);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .page-title p {
      margin: 0.5rem 0 0 0;
      color: var(--apex-text-secondary);
      font-size: 0.9rem;
    }
    
    .settings-content {
      display: grid;
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    
    .setting-item {
      margin-bottom: 1rem;
    }
    
    .setting-item label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: var(--apex-text);
    }
    
    .settings-actions {
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
      padding: 1rem;
      background: var(--apex-surface);
      border: 1px solid var(--apex-border);
      border-radius: 4px;
    }
  `]
})
export class SettingsComponent {}
