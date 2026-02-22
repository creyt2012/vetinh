import asyncio
import os
import sys

# Add current directory to path so imports work
sys.path.append(os.path.join(os.getcwd(), "ai_core"))

from agents.Orchestrator import DeepSkyOrchestrator
from agents.CalibrationAgent import CalibrationAgent
from agents.DeepVisionAgent import DeepVisionAgent
from agents.ScienceAgent import ScienceAgent

async def run_demo():
    print("--- Starting DeepSky Multi-Agent System Demo ---")
    
    orch = DeepSkyOrchestrator()
    orch.register_agent(CalibrationAgent())
    orch.register_agent(DeepVisionAgent())
    orch.register_agent(ScienceAgent())
    
    test_image = "ai_core/test_satellite.png"
    
    # Run the task
    result = await orch.execute_task(test_image)
    
    print("\nProcessing Result:")
    print(result)
    print("\nCheck ai_core/logs/agent_chat.md for the full agent dialogue.")

if __name__ == "__main__":
    asyncio.run(run_demo())
