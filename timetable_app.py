import tkinter as tk
from tkinter import messagebox, ttk
from datetime import datetime

# Mock data (like your Vue mocks)
schedules = [
    {"id": 1, "subject": "Mathematics", "day": "Monday", "start": "09:00", "end": "10:00", "room": "101", "prof": "Dr. Smith", "errors": []},
    {"id": 2, "subject": "Physics", "day": "Monday", "start": "10:00", "end": "11:00", "room": "102", "prof": "Prof. Johnson", "errors": ["Room overlap"]},
    {"id": 3, "subject": "English", "day": "Tuesday", "start": "09:00", "end": "10:00", "room": "101", "prof": "Dr. Smith", "errors": []}
]

class PasswordWindow:
    def __init__(self):
        self.root = tk.Tk()
        self.root.title("School Timetable - Login")
        self.root.geometry("300x150")
        self.root.resizable(False, False)

        tk.Label(self.root, text="Enter Password:", font=("Arial", 12)).pack(pady=20)
        self.password_entry = tk.Entry(self.root, show="*", width=20, font=("Arial", 10))
        self.password_entry.pack(pady=10)
        self.password_entry.bind("<Return>", lambda e: self.check_password())

        tk.Button(self.root, text="Login", command=self.check_password, bg="#007bff", fg="white", font=("Arial", 10)).pack(pady=10)

        self.root.mainloop()

    def check_password(self):
        password = self.password_entry.get()
        if password == "admin123":  # Change this to your desired password
            self.root.destroy()
            MainWindow()  # Open main app
        else:
            messagebox.showerror("Error", "Wrong Password! Try 'admin123'.")

class MainWindow:
    def __init__(self):
        self.root = tk.Tk()
        self.root.title("School Timetable App")
        self.root.geometry("900x600")
        self.root.resizable(True, True)

        # Menu Bar (like calculator buttons)
        menubar = tk.Menu(self.root)
        self.root.config(menu=menubar)
        file_menu = tk.Menu(menubar, tearoff=0)
        menubar.add_cascade(label="File", menu=file_menu)
        file_menu.add_command(label="Add Schedule", command=self.add_schedule)
        file_menu.add_command(label="Exit", command=self.root.quit)

        # Timetable Frame
        frame = tk.Frame(self.root)
        frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)

        # Treeview Table (like your Vue table)
        columns = ("Subject", "Day", "Time", "Room", "Professor", "Status")
        self.tree = ttk.Treeview(frame, columns=columns, show="headings", height=15)
        for col in columns:
            self.tree.heading(col, text=col)
            self.tree.column(col, width=120)
        self.tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

        # Scrollbar
        scrollbar = ttk.Scrollbar(frame, orient=tk.VERTICAL, command=self.tree.yview)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)
        self.tree.configure(yscrollcommand=scrollbar.set)

        # Buttons Frame (bottom)
        btn_frame = tk.Frame(self.root)
        btn_frame.pack(fill=tk.X, padx=10, pady=5)
        tk.Button(btn_frame, text="+ Add Schedule", command=self.add_schedule, bg="#28a745", fg="white").pack(side=tk.LEFT, padx=5)
        tk.Button(btn_frame, text="Delete Selected", command=self.delete_schedule, bg="#dc3545", fg="white").pack(side=tk.LEFT, padx=5)
        tk.Button(btn_frame, text="Refresh", command=self.refresh_table, bg="#007bff", fg="white").pack(side=tk.RIGHT, padx=5)

        self.refresh_table()
        self.root.mainloop()

    def refresh_table(self):
        # Clear table
        for item in self.tree.get_children():
            self.tree.delete(item)
        # Add data
        for sched in schedules:
            time_slot = f"{sched['start']} - {sched['end']}"
            status = "⚠ Conflicts" if sched["errors"] else "✅ Valid"
            self.tree.insert("", tk.END, values=(sched["subject"], sched["day"], time_slot, sched["room"], sched["prof"], status))
            if sched["errors"]:
                self.tree.item(self.tree.get_children()[-1], tags=("error",))
        self.tree.tag_configure("error", background="#f8d7da")

    def add_schedule(self):
        # Simple dialog for add (popup like modal)
        dialog = tk.Toplevel(self.root)
        dialog.title("Add Schedule")
        dialog.geometry("400x300")
        dialog.resizable(False, False)

        # Fields
        tk.Label(dialog, text="Subject:").grid(row=0, column=0, sticky="w", padx=10, pady=5)
        subject_entry = tk.Entry(dialog, width=30)
        subject_entry.grid(row=0, column=1, padx=10, pady=5)

        tk.Label(dialog, text="Day:").grid(row=1, column=0, sticky="w", padx=10, pady=5)
        day_var = tk.StringVar(value="Monday")
        day_combo = ttk.Combobox(dialog, textvariable=day_var, values=["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"], width=27)
        day_combo.grid(row=1, column=1, padx=10, pady=5)

        tk.Label(dialog, text="Start Time:").grid(row=2, column=0, sticky="w", padx=10, pady=5)
        start_entry = tk.Entry(dialog, width=30)
        start_entry.grid(row=2, column=1, padx=10, pady=5)
        start_entry.insert(0, "09:00")

        tk.Label(dialog, text="End Time:").grid(row=3, column=0, sticky="w", padx=10, pady=5)
        end_entry = tk.Entry(dialog, width=30)
        end_entry.grid(row=3, column=1, padx=10, pady=5)
        end_entry.insert(0, "10:00")

        tk.Label(dialog, text="Room:").grid(row=4, column=0, sticky="w", padx=10, pady=5)
        room_var = tk.StringVar(value="101")
        room_combo = ttk.Combobox(dialog, textvariable=room_var, values=["101", "102"], width=27)
        room_combo.grid(row=4, column=1, padx=10, pady=5)

        tk.Label(dialog, text="Professor:").grid(row=5, column=0, sticky="w", padx=10, pady=5)
        prof_var = tk.StringVar(value="Dr. Smith")
        prof_combo = ttk.Combobox(dialog, textvariable=prof_var, values=["Dr. Smith", "Prof. Johnson"], width=27)
        prof_combo.grid(row=5, column=1, padx=10, pady=5)

        def save():
            subj = subject_entry.get()
            day = day_var.get()
            start = start_entry.get()
            end = end_entry.get()
            room = room_var.get()
            prof = prof_var.get()
            if not all([subj, day, start, end, room, prof]):
                messagebox.showerror("Error", "Fill all fields!")
                return
            # Simple validation/AI check
            errors = []
            if end <= start:
                errors.append("End time after start!")
            if day == "Monday" and start == "09:00":
                errors.append("Conflict: Math already here!")
            if errors:
                messagebox.showwarning("AI Suggestion", f"Errors: {'; '.join(errors)}\nTry Tuesday 10:00.")
            else:
                new_id = max(s["id"] for s in schedules) + 1 if schedules else 1
                schedules.append({"id": new_id, "subject": subj, "day": day, "start": start, "end": end, "room": room, "prof": prof, "errors": errors})
                self.refresh_table()
                messagebox.showinfo("Success", "Schedule added!")
                dialog.destroy()

        tk.Button(dialog, text="Save", command=save, bg="#28a745", fg="white").grid(row=6, column=0, columnspan=2, pady=20)
        tk.Button(dialog, text="Cancel", command=dialog.destroy, bg="#6c757d", fg="white").grid(row=7, column=0, columnspan=2, pady=5)

    def delete_schedule(self):
        selected = self.tree.selection()
        if not selected:
            messagebox.showwarning("Warning", "Select a row to delete!")
            return
        if messagebox.askyesno("Confirm", "Delete selected schedule?"):
            item = self.tree.item(selected[0])
            subj = item["values"][0]
            # Find and remove (mock—use ID in real)
            for sched in schedules:
                if sched["subject"] == subj:
                    schedules.remove(sched)
                    break
            self.refresh_table()
            messagebox.showinfo("Success", "Deleted!")

if __name__ == "__main__":
    PasswordWindow()  # Starts with password popup
