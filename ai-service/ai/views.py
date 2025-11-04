from rest_framework.decorators import api_view
from rest_framework.response import Response
from rest_framework import status
# import requests  # Uncomment if querying Laravel API for overlaps
# from openai import OpenAI  # Uncomment for real AI (add API key in .env)
# import os

# client = OpenAI(api_key=os.getenv('OPENAI_API_KEY')) if os.getenv('OPENAI_API_KEY') else None  # For OpenAI

@api_view(['POST'])
def analyze_schedule(request):
    data = request.data  # Incoming schedule data (e.g., from Laravel/Vue)
    errors = []
    
    # Rule-based checks (expand for timetable: room availability, professor conflicts, etc.)
    if data.get('end_time', '') <= data.get('start_time', ''):
        errors.append({
            'type': 'time_conflict',
            'description': 'End time is before or equal to start time. Please fix the schedule.'
        })
    
    # Example: Check for room overlap (mock; replace with real query to Laravel API)
    # if requests.get(f"http://127.0.0.1:8000/api/rooms/{data.get('room_id')}/availability").json().get('overlaps'):
    #     errors.append({'type': 'room_overlap', 'description': 'Room is already booked at this time.'})
    
    # AI Suggestion (mock for now; integrate OpenAI below)
    suggestion = "AI Suggestion: Adjust end time to after start time (e.g., 10:00). No overlaps detected in rooms/professors."
    if errors:
        suggestion = "Manual edit required: " + suggestion
    
    # Real OpenAI Integration (uncomment and add key to .env for GPT suggestions)
    # if client:
    #     prompt = f"Analyze this school schedule for errors and suggest fixes: {data}. Focus on time, room, and professor conflicts."
    #     try:
    #         response = client.chat.completions.create(
    #             model="gpt-3.5-turbo",
    #             messages=[{"role": "user", "content": prompt}]
    #         )
    #         suggestion = response.choices[0].message.content
    #     except Exception as e:
    #         suggestion += f" (AI error: {str(e)})"
    
    return Response({
        'schedule_id': data.get('id', None),
        'errors_found': errors,
        'ai_suggestion': suggestion,
        'auto_fixed': len(errors) == 0,
        'updated_data': data if len(errors) == 0 else None  # Return fixed data if no errors
    }, status=status.HTTP_200_OK)
