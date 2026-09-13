<script setup>
import { ref } from 'vue';
import AddCommentModal from './AddCommentModal.vue';
import CommentItem from './CommentItem.vue';

const props = defineProps({
    post: { type: Object, required: true },
});

const showModal = ref(false);

function handleCreated(comment) {
    props.post.comments.unshift(comment);
}

function handleDeleted(commentId) {
    props.post.comments = props.post.comments.filter((c) => c.id !== commentId);
}
</script>

<template>
    <section class="card">
        <header class="post-header">
            <div class="avatar">{{ post.author.name.charAt(0) }}</div>
            <div>
                <h2 class="post-title">{{ post.title }}</h2>
                <span class="muted" style="font-size: 0.8125rem">by {{ post.author.name }}</span>
            </div>
        </header>

        <p class="post-body">{{ post.body }}</p>

        <div class="section-heading">
            <span class="muted" style="font-size: 0.875rem">
                {{ post.comments.length }} comment{{ post.comments.length === 1 ? '' : 's' }}
            </span>
            <button type="button" class="btn btn--primary btn--sm" @click="showModal = true">
                + Add comment
            </button>
        </div>

        <div v-if="post.comments.length" class="comment-list">
            <CommentItem
                v-for="comment in post.comments"
                :key="comment.id"
                :comment="comment"
                @deleted="handleDeleted"
            />
        </div>
        <p v-else class="muted" style="font-size: 0.875rem">No comments yet - be the first!</p>

        <AddCommentModal
            v-model="showModal"
            :post-id="post.id"
            @created="handleCreated"
        />
    </section>
</template>
