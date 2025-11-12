# System Architecture and Security Overview

This document provides a comprehensive overview of the architectural patterns and security measures implemented in this application.

## 1. Application Architecture: Decoupled SPA

The application is built on a modern, decoupled architecture that separates the frontend from the backend.

-   **Frontend (Client-Side):** A **Single-Page Application (SPA)** built with **Vue.js**. The Vue application is responsible for rendering the user interface and managing user interactions within the browser. It is compiled and served as a set of static assets (JavaScript, CSS).

-   **Backend (Server-Side):** A robust API built with **Laravel (PHP)**. The backend is responsible for all business logic, database interactions, user authentication, and communication with the AI scheduling engine.

### Why this architecture?

-   **Decoupling:** The frontend and backend are independent. As long as the API contract (the routes and data format) is maintained, they can be developed, deployed, and scaled separately.
-   **Rich User Experience:** An SPA provides a fast, fluid, and responsive user experience, similar to a desktop application, as it doesn't require a full page reload for every interaction.
-   **Flexibility:** The same backend API can be used to serve other clients in the future, such as a mobile application.

---

## 2. API and Data Strategy: API Routes with JSON

All communication between the Vue.js frontend and the Laravel backend occurs through a well-defined set of API routes.

-   **API Routes:** Defined in `routes/api.php`, these routes form the contract between the client and server.
-   **Data Format:** **JSON (JavaScript Object Notation)** is used as the exclusive data format for all API requests and responses.

### Why this strategy?

-   **Standardization:** JSON is the de facto, language-independent standard for modern APIs, understood natively by both the Vue.js frontend and the Python AI script.
-   **Efficiency:** JSON is lightweight and fast to parse, minimizing data payload size and improving application performance.
-   **AI Communication:** This architecture is ideal for integrating the Python AI. Laravel gathers data from the database, packages it into a JSON object, and sends it to the AI. The AI, in turn, returns its results in JSON format, which Laravel can easily process.

---

## 3. AI Integration: On-Demand Child Process

The Python-based AI scheduling engine is not run as a separate, continuously-running server (e.g., with Flask). Instead, it is invoked as an **on-demand command-line process** directly from the Laravel backend.

-   **Mechanism:** The `ScheduleController.php` file uses PHP's built-in `shell_exec()` function to execute the Python script (`ai/newtry.py`).
-   **Data Transfer:** Laravel passes the required data (rooms, faculty, subjects, etc.) to the script as a JSON file via a command-line argument.

### Why this approach?

-   **Architectural Simplicity:** It avoids the complexity of managing, monitoring, and deploying a second, persistent web server (a microservice). The entire application can be deployed as a single unit.
-   **Resource Efficiency:** The computationally intensive Python script only consumes significant CPU and memory *while it is actively generating a schedule*. When idle, it uses zero resources, making it a cost-effective solution for a task that is not run constantly.

---

## 4. Authentication Model: Hybrid Stateful/Stateless Approach

The system correctly uses two different authentication mechanisms for two distinct purposes, following the best practices for securing a Laravel SPA.

### Phase 1: Stateful Web Session (For Initial App Load)

-   **Purpose:** To secure the initial loading of the application shell itself.
-   **Mechanism:** When a user first visits the site, the request is handled by `routes/web.php`. These routes are part of the `web` middleware group, which starts a traditional, stateful PHP session.
-   **Key Security Benefit:** This enables **CSRF (Cross-Site Request Forgery) Protection**. It ensures that the application is being loaded legitimately in a user's browser and protects against malicious form submissions from other sites.

### Phase 2: Stateless API Token (For All API Communication)

-   **Purpose:** To secure all API endpoints.
-   **Mechanism:** The application uses **Laravel Sanctum**. Upon successful login via the `/api/login` route, the backend generates a secure, self-contained API token. This token is stored on the client-side (`localStorage`) and is sent in the `Authorization` header of every subsequent API request.
-   **Key Security Benefit:** This approach is **stateless**. The server does not need to store session information for API users, making it highly scalable. Each API request is authenticated independently based on the validity of the token.

---

## 5. Comprehensive Security Measures

The application leverages built-in framework features to protect against a wide range of common web vulnerabilities.

-   **SQL Injection Prevention:** The use of Laravel's **Eloquent ORM** and **Query Builder** with parameter binding makes the application inherently resistant to SQL injection attacks. All database queries are built safely, separating the query structure from user-provided data.

-   **Cross-Site Scripting (XSS) Prevention:** Data rendered in the frontend using Vue's double curly brace syntax (`{{ data }}`) is automatically escaped. This converts potentially malicious HTML and script tags into plain text, preventing them from being executed in the user's browser.

-   **Cross-Site Request Forgery (CSRF) Protection:** As mentioned above, Laravel's `web` middleware automatically generates and validates CSRF tokens for the initial application load, preventing malicious third-party sites from making unauthorized requests on behalf of the user.

-   **Password Security:** User passwords are never stored in plain text. Laravel uses a strong, one-way **hashing algorithm (Bcrypt)** to create a secure hash of the user's password. This means that even if the database were compromised, the original passwords would remain unknown.

-   **Secure Configuration:** All sensitive configuration data (database passwords, API keys, etc.) is stored in the `.env` file. This file is explicitly excluded from version control via `.gitignore`, preventing secret credentials from being exposed in the codebase.

-   **Role-Based Access Control (RBAC):** The system differentiates between user types (e.g., 'admin', 'faculty'), allowing both the frontend and backend to control access to specific features and data based on the authenticated user's role. This enforces the principle of least privilege.

---

## 6. Architectural Decisions and Justifications (FAQ)

This section addresses common "why" questions regarding the technologies and patterns chosen for this project.

### Why Choose a Single-Page Application (SPA)?

A SPA architecture was chosen over a traditional Multi-Page Application (MPA) for several key reasons:

-   **Enhanced User Experience:** SPAs are faster and more fluid. After the initial page load, only dynamic data is sent over the network, not entire HTML pages. This eliminates page flashes and creates a smooth, desktop-like experience.
-   **Clean Separation of Concerns:** It enforces a strict separation between the frontend (presentation logic) and the backend (business logic). This makes the codebase cleaner, easier to maintain, and easier for teams to work on in parallel.
-   **API Reusability:** By building the backend as a standalone API, it can be easily consumed by other clients in the future (e.g., a native mobile app) without any changes to the backend code.

### Why Select Vue.js for the Frontend?

Vue.js was chosen as the frontend framework for its balance of power and simplicity:

-   **Gentle Learning Curve:** Vue's API is simple and its documentation is widely considered the best in the industry, allowing for rapid development.
-   **Performance:** Vue's reactivity system is highly optimized, ensuring the UI stays fast even with complex data updates.
-   **Component-Based Architecture:** It encourages the development of small, reusable, and self-contained components, leading to a more organized and maintainable codebase.
-   **Progressive Framework:** Vue can be adopted incrementally. You can use it to control small parts of a page or to build a full-scale, complex application like this one.

### Why Use Laravel for the Backend?

Laravel was selected as the backend framework due to its focus on developer experience and its robust, "batteries-included" feature set:

-   **Elegant Syntax and Tooling:** Laravel is designed to make common tasks easy, from database migrations and ORM (Eloquent) to routing and authentication. This accelerates the development process.
-   **Built-in Security:** Laravel provides out-of-the-box protection for common vulnerabilities like SQL injection, XSS, and CSRF, providing a secure foundation for the application.
-   **Scalability:** It has a rich ecosystem of tools for caching, queuing, and more, allowing the application to scale as user load increases.
-   **Strong Community:** As one of the most popular PHP frameworks, Laravel has a massive community, extensive documentation, and a vast library of third-party packages.

### What are the Pros and Cons of using an ORM (Eloquent)?

Using Laravel's Eloquent ORM instead of writing raw SQL queries is a deliberate trade-off:

-   **Pros:**
    -   **Development Speed:** It is much faster to write `Professor::all()` than `SELECT * FROM professors`.
    -   **Security:** It automatically protects against SQL injection.
    -   **Database Agnostic:** It provides a consistent API for interacting with different database systems (e.g., MySQL, PostgreSQL), making it easier to switch databases if needed.
    -   **Readability:** Eloquent's fluent syntax is often more readable and maintainable than complex SQL queries.

-   **Cons:**
    -   **Performance Overhead:** For very complex queries, a hand-tuned raw SQL query can sometimes be more performant than what the ORM generates.
    -   **Abstraction:** It can hide the complexity of the underlying database operations, which can be a drawback if a developer is not mindful of the queries being generated (e.g., the N+1 problem).

### What are the limitations of the current AI integration method?

The current method of using `shell_exec()` is simple and resource-efficient, but it has one primary limitation:

-   **Synchronous Execution:** `shell_exec()` is a **blocking** call. This means the PHP process will halt and wait until the Python script has completely finished its execution. For the current AI, this might take several seconds or even minutes. During this time, the user's request is frozen.
-   **When to Upgrade:** If the AI task becomes significantly longer (e.g., > 60 seconds) or if many users need to run it simultaneously, this synchronous approach would become a bottleneck. The next architectural step would be to switch to an **asynchronous** model using a **job queue** (like Laravel Queue with Redis or RabbitMQ). In that model, Laravel would dispatch a "GenerateSchedule" job to the queue and immediately return a response to the user. A separate worker process would then pick up the job from the queue and execute the Python script in the background, notifying the user upon completion.

### How is the frontend build process managed?

The frontend development and build process is managed by modern JavaScript tooling:

-   **Node.js & npm:** Node.js provides the runtime environment for our tools. `npm` (Node Package Manager) is used to install and manage all frontend dependencies (like Vue, axios, etc.).
-   **`package.json`:** This file is the heart of the frontend project. It lists all dependencies and defines scripts for common tasks (e.g., `npm run dev`, `npm run build`).
-   **Vite:** We use Vite as our frontend build tool. It provides:
    -   A highly optimized development server with **Hot Module Replacement (HMR)**, which allows code changes to be reflected in the browser instantly without a full page reload.
    -   An efficient build process that bundles and minifies all JavaScript and CSS files for production, ensuring the smallest possible file sizes for faster loading times.

---

## 7. AI and Processing Algorithms

### AI Scheduling Algorithm: A Hybrid Approach

The AI for schedule generation does **not** use a simple greedy algorithm. Instead, it employs a sophisticated, two-stage hybrid approach that combines **Constraint Programming** with **Heuristic-Guided Pre-processing**.

**Stage 1: Heuristic-Guided Pre-processing and Candidate Selection**

Before attempting to solve the complex scheduling puzzle, the script intelligently pre-processes the data to guide the solver towards a high-quality, human-like solution. This is a form of **heuristic search**, where rules of thumb are used to narrow down the vast number of possibilities.

-   **Faculty-Subject Matching:** The system uses a multi-layered heuristic to create a prioritized list of possible teacher-subject assignments.
    1.  **Department Inference:** It first infers the academic department for each subject based on its course code (e.g., "CS" -> Computer Science) or keywords in its title.
    2.  **Perfect Matches (High Priority):** It gives the highest priority to matching a subject with a professor from the same inferred department.
    3.  **Fallback Tiers (Lower Priority):** If no "perfect match" is possible, it considers other options, such as a professor from a related department, but these are marked as less desirable.
-   **Resource-Specific Constraints:**
    -   **Lab vs. Lecture:** It identifies subjects that require a laboratory and ensures they are only considered for assignment in rooms designated as "labs."
    -   **Faculty Unavailability:** It parses each professor's unavailable time slots and prevents any assignments during those times, including a 60-minute buffer.

**Stage 2: Constraint Programming with Google's CP-SAT Solver**

This is the core of the AI. The scheduling problem is formally modeled and solved using **Google's OR-Tools**, specifically the **CP-SAT (Constraint Programming - Satisfiability) solver**. This is a powerful and industry-standard tool for combinatorial optimization problems.

The model is defined by three key components:

1.  **Decision Variables:** A boolean variable is created for every single possible combination of `(Subject, Professor, Room, Time Slot)`. The solver's job is to decide whether to set each variable to `true` (scheduled) or `false` (not scheduled).

2.  **Hard Constraints (The Rules):** These are the absolute, unbreakable rules that the final schedule *must* follow.
    -   **No Double-Booking:** A professor, room, or student section cannot be in two places at once.
    -   **Assign Once:** Each class can only be scheduled once.
    -   **Workload Limits:** The total number of units assigned to a professor cannot exceed their maximum contractual load.

3.  **Objective Function (The Goal):** The solver doesn't just find a valid schedule; it tries to find the *best* schedule by maximizing a score based on a weighted list of goals. This is a classic example of a **multi-objective optimization** problem. The objectives, in order of importance, are:
    -   **Maximize Scheduled Classes (Weight: 10,000):** The absolute top priority is to get as many classes scheduled as possible.
    -   **Prioritize "Perfect" Faculty Matches (Weight: ~3,000-8,000):** Strongly reward the solver for choosing the "perfect match" assignments identified in the heuristic stage.
    -   **Prefer Full-Time Faculty (Weight: 2,000):** Encourage the use of full-time faculty to fill their schedules.
    -   **Balance Faculty Load (Weight: 50):** Gently encourage the solver to distribute the workload evenly among faculty.
    -   **Penalize Cross-Department Assignments (Weight: -3,000):** Explicitly punish the solver for making undesirable cross-department assignments.

### Conflict Detection Algorithm

The `conflict_checker.py` script uses a more straightforward but effective algorithm. It is a **brute-force search** that systematically checks for constraint violations.

1.  **Group by Resource:** It first groups all scheduled classes by the resource being used (by Faculty, by Room, and by Student Section).
2.  **Iterate and Compare:** For each group, it iterates through every possible pair of classes and checks for time overlaps. If two classes in the same group have overlapping times on the same day, it flags a conflict.

This approach is computationally simple and guarantees that all direct time conflicts will be found.

### Frontend Greedy Algorithm for Unassigned Subjects

In addition to the core CP-SAT solver, the frontend (`PendingPanel.vue`) employs a **Greedy Algorithm** to assist the user in resolving any subjects that the AI could not automatically place.

1.  **Pre-calculated Suggestions:** The Python backend provides a list of high-quality potential assignments for each unassigned subject, scored and sorted based on the same heuristics used by the main solver (faculty match, room suitability, etc.).
2.  **Locally Optimal Choice:** When the user clicks "Auto Assign All," the frontend code iterates through each unassigned subject and immediately assigns it the **highest-scoring** available suggestion.
3.  **Greedy Nature:** This is considered a "greedy" approach because it makes the best local choice at each step without re-evaluating the global state. It does not reconsider a choice once made. For example, assigning Subject A to its best-fit professor might prevent Subject B from being assigned at all, but the greedy algorithm does not look ahead to see this. It simply provides a fast and effective way to handle the remaining subjects based on the powerful suggestions from the backend.

---

## 8. Deployment and Hosting Considerations

The application's hybrid architecture imposes specific requirements that make it unsuitable for standard free hosting services.

### Why Free Hosting is Not Viable

1.  **High Resource Requirements:** The core AI scheduling process, which uses Google's CP-SAT solver, is computationally intensive. It requires significant CPU and RAM to solve complex scheduling problems in a timely manner. Free hosting tiers are typically heavily throttled and would cause the AI process to fail or time out.

2.  **Hybrid Environment (PHP + Python):** The system requires a hosting environment that can simultaneously run both the Laravel (PHP) web application and a separate Python runtime for the AI engine. Most free platforms are designed to host a single application type (e.g., only PHP or only Node.js) and do not support the multi-process, multi-language environment this project needs.

3.  **Complex Dependencies:** The Python environment depends on specific scientific computing libraries, most notably Google's OR-Tools. Installing these specialized libraries is often difficult or impossible on the restrictive, shared environments provided by free hosting services.

### Recommended Hosting Strategy

Due to these constraints, the recommended deployment target is a **Virtual Private Server (VPS)** or a similar cloud computing instance (e.g., from AWS, Google Cloud, or DigitalOcean). This approach provides:

-   **Dedicated Resources:** Guarantees the necessary CPU and RAM for the AI to run effectively.
-   **Full Environment Control:** Allows for the installation of both PHP and Python, along with all required libraries and dependencies.
-   **Scalability:** Provides a clear path for scaling resources as the application's usage grows.