export interface RelationshipSettings {
    started_on: string | null;
    name: string | null;
    notifications_enabled: boolean;
}

export interface RelationshipMilestone {
    date: string;
    date_label: string;
    type: string;
    description: string;
    days_remaining: number;
}

export interface RelationshipSummary {
    days_together: number;
    human_label: string;
    next_milestone: RelationshipMilestone | null;
    upcoming_milestones: RelationshipMilestone[];
}
