# ⚽ Champions League Simulator

Elite European football tournament simulator built with **Laravel 11** and **Vue.js 3**. This application allows users to draw groups, simulate matches week by week, and view real-time championship predictions based on team strengths and performance.

![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge&logo=vue.js)
![TailwindCSS](https://img.shields.io/badge/Tailwind-CSS-38B2AC?style=for-the-badge&logo=tailwind-css)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-Cloud-4169E1?style=for-the-badge&logo=postgresql)

## 🚀 Features

-   **Dynamic Group Draw:** Randomly distribute elite teams into multiple groups.
-   **Automated Fixtures:** Generates a professional home-and-away schedule.
-   **Smart Simulation:** A specialized algorithm determines match results based on team `strength` levels and home-field advantage.
-   **Real-time Standings:** Instant updates on Points, Goal Difference, and Win/Loss records.
-   **Championship Predictions:** Starting from Week 4, the system calculates the probability of each team winning the group using a complex weighted algorithm.
-   **Responsive Design:** Fully optimized for mobile and desktop with a "Dark Mode" aesthetic.

## 🛠️ Tech Stack

-   **Backend:** PHP 8.2+ / Laravel 11
-   **Frontend:** Vue.js 3 (Composition API)
-   **Database:** PostgreSQL (Hosted on Google Cloud Platform)
-   **Infrastructure:** Docker & Docker Compose

## 🐳 Docker Quick Start (Recommended)

This project is Dockerized to provide a "plug-and-play" experience.

1.  **Clone & Build Assets:**
    ```powershell
    git clone [https://github.com/arslanmertugur/insider-case.git](https://github.com/arslanmertugur/insider-case.git)
    cd insider-case
    npm install
    npm run build
    ```

2.  **Run Containers:**
    ```powershell
    docker-compose up -d
    ```

3.  **Setup Database:**
    ```powershell
    docker exec -it insider_case_app composer install
    docker exec -it insider_case_app php artisan migrate --seed
    ```

4.  **Access:** Open [http://localhost:8080](http://localhost:8080)

## 🔒 Security & Environment Note

> **Important:** The `.env` file is intentionally included in this repository for this case study. It contains connection strings for a **GCP PostgreSQL** instance. 
> 
> - **Reviewer Convenience:** This allows for immediate testing without local database setup.
> - **Safety:** The database is protected via GCP IP Whitelisting and restricted user roles.
> - **Production Note:** In a real production scenario, environment variables are never committed to version control and are managed via secret management services.

## 🧠 Simulation Logic

The simulation uses a weighted probability distribution. Each team has a `strength` attribute (1-100).
-   **Base Chance:** Calculated from the difference in strength.
-   **Home Advantage:** Small boost to the home team's scoring probability.
-   **Predictions:** The prediction algorithm kicks in after Week 4, considering current points, goal difference, and remaining strength of schedule.

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).