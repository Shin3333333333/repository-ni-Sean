from django.urls import path
from . import views

urlpatterns = [
    path('analyze/', views.analyze_schedule, name='analyze_schedule'),
]
