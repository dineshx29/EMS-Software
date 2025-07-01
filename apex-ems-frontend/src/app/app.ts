import { Component } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, CommonModule],
  templateUrl: './app.html',
  styleUrl: './app.scss'
})
export class AppComponent {
  title = 'APEX EMS';

  currentUser = {
    name: 'Administrator',
    email: 'admin@apexems.com',
    role: 'Super Admin'
  };

  logout() {
    // Implement logout logic here
    console.log('Logout clicked');
    // For now, just show an alert
    alert('Logout functionality will be implemented with authentication service');
  }
}
