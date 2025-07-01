import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-reports',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="reports-container">
      <div class="page-header">
        <div class="page-title">
          <h1><i class="pi pi-file-pdf"></i> Reports & Analytics</h1>
          <p>Generate and view system reports</p>
        </div>
        <div class="page-actions">
          <button class="apex-button apex-button-success">
            <i class="pi pi-plus"></i> Create Report
          </button>
        </div>
      </div>
      
      <div class="reports-grid">
        <div class="report-card">
          <div class="report-icon">
            <i class="pi pi-users"></i>
          </div>
          <div class="report-content">
            <h3>Employee Report</h3>
            <p>Comprehensive employee data and statistics</p>
            <button class="apex-button">Generate</button>
          </div>
        </div>
        
        <div class="report-card">
          <div class="report-icon">
            <i class="pi pi-chart-bar"></i>
          </div>
          <div class="report-content">
            <h3>Performance Analytics</h3>
            <p>Department and individual performance metrics</p>
            <button class="apex-button">Generate</button>
          </div>
        </div>
        
        <div class="report-card">
          <div class="report-icon">
            <i class="pi pi-calendar"></i>
          </div>
          <div class="report-content">
            <h3>Attendance Report</h3>
            <p>Employee attendance and leave records</p>
            <button class="apex-button">Generate</button>
          </div>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .reports-container {
      padding: 0;
      height: 100%;
      overflow-y: auto;
    }
    
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
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
    
    .reports-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
    }
    
    .report-card {
      background: var(--apex-surface);
      border: 1px solid var(--apex-border);
      border-radius: 8px;
      padding: 1.5rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s ease;
    }
    
    .report-card:hover {
      transform: translateY(-2px);
    }
    
    .report-icon {
      width: 60px;
      height: 60px;
      background: var(--apex-primary);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
    }
    
    .report-content {
      flex: 1;
    }
    
    .report-content h3 {
      margin: 0 0 0.5rem 0;
      color: var(--apex-text);
    }
    
    .report-content p {
      margin: 0 0 1rem 0;
      color: var(--apex-text-secondary);
      font-size: 0.9rem;
    }
  `]
})
export class ReportsComponent {}
