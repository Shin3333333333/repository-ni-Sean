// File: laravel/resources/js/views/panels/test_PendingPanel.spec.js
import { mount } from "@vue/test-utils";


import PendingPanel from "./PendingPanel.vue";

import { describe, it, expect, beforeEach, afterEach, vi } from "vitest";
// lightweight local replacement for flush-promises (avoids extra dependency)
const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));
/*
 Note: user requested "test_PendingPanel.vue" placement. Vitest convention uses .spec.js/.ts;
 place this file alongside the component. Adjust filename if you strictly need .vue.
*/

describe("PendingPanel.vue - basic pending save/load/assign flows", () => {
  let originalFetch;

  beforeEach(() => {
    // keep original fetch
    originalFetch = global.fetch;
  });

  afterEach(() => {
    // restore fetch
    global.fetch = originalFetch;
    vi.restoreAllMocks();
  });

  function makeMockFetch(responses = {}) {
    return vi.fn().mockImplementation(async (input, init = {}) => {
      const url = typeof input === "string" ? input : input.url;
      // simple routing by URL
      if (url.endsWith("/api/pending-schedules") && (!init || init.method === undefined)) {
        // GET batches
        return {
          ok: true,
          json: async () => responses.listResponse || {
            pending: [
              {
                batch_id: "batch123",
                academicYear: "2024-2025",
                semester: "First",
                created_at: "2025-01-01T00:00:00Z"
              }
            ]
          }
        };
      }

      if (url.endsWith("/api/pending-schedules/batch123")) {
        // GET batch details
        return {
          ok: true,
          json: async () => responses.batchDetailsResponse || {
            grouped: [
              {
                id: 1,
                courseCode: "CS101",
                faculty: "Dr. Alice",
                subject: "Intro CS",
                time: "08:00",
                room_name: "R101",
                courseSection: "A",
                units: 3
              }
            ],
            unassigned: [
              {
                id: 2,
                course_code: "CS102",
                faculty: null,
                subject_display: "Data Structures",
                time_slot_label: "",
                room_name: "",
                course_section: "B",
                units: 3,
                possible_assignments: [
                  {
                    faculty_name: "Dr. Bob",
                    time: "10:00",
                    room_name: "R202",
                    units: 3
                  }
                ]
              }
            ]
          }
        };
      }

      if (url.endsWith("/update") && init && init.method === "PUT") {
        // saveChanges endpoint
        // echo back success
        return {
          ok: true,
          json: async () => ({ success: true, received: JSON.parse(init.body || "{}") })
        };
      }

      // default fallback
      return { ok: true, json: async () => ({}) };
    });
  }

  it("loads batches on mount and displays batch row", async () => {
    global.fetch = makeMockFetch();

    const wrapper = mount(PendingPanel);
    // wait for mounted async fetch
    await flushPromises();

    // verify vm batchList
    expect(wrapper.vm.batchList).toBeTruthy();
    expect(wrapper.vm.batchList.length).toBeGreaterThan(0);
    expect(wrapper.vm.batchList[0].batch_id).toBe("batch123");

    // verify DOM shows the batch id
    const firstRow = wrapper.find("tbody tr");
    expect(firstRow.exists()).toBe(true);
    expect(wrapper.html()).toContain("batch123");
  });

  it("openBatch preserves grouped and unassigned with possible_assignments", async () => {
    global.fetch = makeMockFetch();

    const wrapper = mount(PendingPanel);
    await flushPromises();

    // call openBatch directly
    await wrapper.vm.openBatch("batch123");
    await flushPromises();

    // pendingSchedules should include grouped (id=1) and unassigned (id=2)
    const ids = wrapper.vm.pendingSchedules.map((s) => s.id);
    expect(ids).toContain(1);
    expect(ids).toContain(2);

    // find the unassigned row object
    const unassigned = wrapper.vm.pendingSchedules.find((r) => String(r.id) === "2" || r.id === 2);
    expect(unassigned).toBeTruthy();
    // ensure possible_assignments preserved on the unassigned entry
    expect(Array.isArray(unassigned.possible_assignments)).toBe(true);
    expect(unassigned.possible_assignments.length).toBeGreaterThan(0);

    // the modal should be visible
    expect(wrapper.vm.showModal).toBe(true);
  });

  it("assignSuggestion updates the unassigned row and removes possible_assignments", async () => {
    global.fetch = makeMockFetch();

    const wrapper = mount(PendingPanel);
    await flushPromises();

    await wrapper.vm.openBatch("batch123");
    await flushPromises();

    // locate unassigned row id
    const unassigned = wrapper.vm.pendingSchedules.find((r) => r.possible_assignments && r.possible_assignments.length);
    expect(unassigned).toBeTruthy();
    const rowId = unassigned.id;
    const suggestion = unassigned.possible_assignments[0];

    // call assignSuggestion
    wrapper.vm.assignSuggestion(rowId, suggestion);
    await flushPromises();

    // ensure the row has updated fields
    const updatedRow = wrapper.vm.pendingSchedules.find((r) => r.id === rowId);
    expect(updatedRow.faculty).toBe("Dr. Bob");
    expect(updatedRow.time).toBe("10:00");
    expect(updatedRow.classroom).toBe("R202");
    expect(Number(updatedRow.units)).toBe(3);

    // possible_assignments should be removed
    expect(updatedRow.possible_assignments).toBeUndefined();
  });

  it("saveChanges sends PUT with the current state including assigned updates", async () => {
    const customResponses = {
      batchDetailsResponse: {
        grouped: [],
        unassigned: [
          {
            id: "u100",
            course_code: "CS200",
            faculty: null,
            subject_display: "Algo",
            time_slot_label: "",
            room_name: "",
            course_section: "C",
            units: 2,
            possible_assignments: [
              { faculty_name: "Dr. Eve", time: "14:00", room_name: "R303", units: 2 }
            ]
          }
        ]
      }
    };
    const mockFetch = makeMockFetch({ batchDetailsResponse: customResponses.batchDetailsResponse });
    global.fetch = mockFetch;

    const wrapper = mount(PendingPanel);
    await flushPromises();

    await wrapper.vm.openBatch("batch123");
    await flushPromises();

    // assign suggestion to change faculty
    const unassigned = wrapper.vm.pendingSchedules.find((r) => r.id === "u100" || r.id === "u100");
    expect(unassigned).toBeTruthy();
    const suggestion = unassigned.possible_assignments[0];
    wrapper.vm.assignSuggestion(unassigned.id, suggestion);
    await flushPromises();

    // spy fetch calls to detect PUT invocation
    // call saveChanges
    await wrapper.setData({ selectedBatch: "batch123" }); // ensure selectedBatch set
    await wrapper.vm.saveChanges();
    await flushPromises();

    // find last call to fetch where method === PUT
    const calls = mockFetch.mock.calls;
    const putCall = calls.find((call) => {
      const url = call[0];
      const init = call[1] || {};
      return typeof url === "string" && url.endsWith("/api/pending-schedules/batch123/update") && init.method === "PUT";
    });
    expect(putCall).toBeTruthy();

    const putInit = putCall[1];
    expect(putInit.headers["Content-Type"]).toBe("application/json");
    const body = JSON.parse(putInit.body);
    expect(Array.isArray(body.schedules)).toBe(true);
    // schedules should include our assigned row with faculty Dr. Eve
    const saved = body.schedules.find((s) => String(s.id) === String(unassigned.id));
    expect(saved).toBeTruthy();
    expect(saved.faculty).toBe("Dr. Eve");
  });
});